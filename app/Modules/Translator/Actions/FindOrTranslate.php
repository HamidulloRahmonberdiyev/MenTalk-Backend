<?php

declare(strict_types=1);

namespace App\Modules\Translator\Actions;

use App\Modules\Account\Models\User;
use App\Modules\Ai\Contracts\AiClient;
use App\Modules\Ai\DTO\AiRequest;
use App\Modules\Ai\Exceptions\AiRejected;
use App\Modules\Ai\Services\PromptStore;
use App\Modules\Translator\DTO\TranslationQuery;
use App\Modules\Translator\Models\Translation;
use App\Modules\Translator\Schemas\TranslationSchema;
use App\Modules\Translator\Support\TranslatorLanguages;
use App\Modules\Vocabulary\Services\Dictionary;
use App\Shared\Exceptions\QuotaExceeded;
use App\Shared\Http\Middleware\DailyQuota;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * The translator: our own store first, the AI only for text nobody has translated yet. Each AI answer
 * is stored for every learner, and a single word also enters the global dictionary.
 */
final readonly class FindOrTranslate
{
    public function __construct(private AiClient $ai, private PromptStore $prompts, private Dictionary $dictionary) {}

    /** @return array{0: array<string, mixed>, 1: bool} the result, and whether it came from the store */
    public function __invoke(TranslationQuery $query, User $user): array
    {
        $key = $query->key();

        if ($stored = Translation::query()->where('key', $key)->first()) {
            Translation::query()->whereKey($stored->getKey())->update(['hits' => DB::raw('hits + 1'), 'last_hit_at' => now()]);

            return [$stored->result, true];
        }

        // Only a real AI call counts against the learner's daily allowance.
        if (! DailyQuota::consume('lookups', $user->id)) {
            throw new QuotaExceeded;
        }

        $result = $this->resultFrom($this->ai->json(new AiRequest(
            system: $this->systemPrompt($query),
            contents: [['role' => 'user', 'parts' => [['text' => $query->text]]]],
            schema: TranslationSchema::get(),
            temperature: 0.2,
        )), $query);

        // Two learners typing the same text at once: the first answer stored wins, both are served.
        $stored = Translation::query()->createOrFirst(['key' => $key], [
            'source_language' => $query->source,
            'target_language' => $query->target,
            'ui_language' => $query->uiLanguage->value,
            'uz_script' => $query->uzScript,
            'text' => $query->text,
            'result' => $result,
        ]);

        $this->addToDictionary($query, $stored->result, $user);

        return [$stored->result, false];
    }

    private function systemPrompt(TranslationQuery $query): string
    {
        $target = TranslatorLanguages::name($query->target);

        return strtr($this->prompts->get('translator'), [
            '{source_rule}' => $query->isAuto()
                ? 'The input language is unknown: detect it (it may be Uzbek in Latin or Cyrillic script, Russian, English, or mixed).'
                : 'The input is '.TranslatorLanguages::name($query->source).'.',
            '{target}' => $target,
            '{fallback}' => TranslatorLanguages::name(TranslatorLanguages::fallbackFor($query->target)),
            '{uz_script}' => $query->uzScript === 'cyrillic' ? 'Cyrillic script' : 'Latin script (the modern official alphabet)',
            '{ui_language}' => $query->uiLanguage->englishName(),
        ]);
    }

    /**
     * The AI answer checked and shaped as the API returns it. Stored as is, so nothing the model got
     * wrong in shape may get through: a bad answer would be served to everyone afterwards.
     */
    private function resultFrom(array $raw, TranslationQuery $query): array
    {
        $translation = self::text($raw['translation'] ?? null);

        if ($translation === '') {
            throw new AiRejected('The translation came back empty.');
        }

        $source = TranslatorLanguages::isKnown($raw['sourceLanguage'] ?? null) ? $raw['sourceLanguage'] : $query->source;
        $target = TranslatorLanguages::isKnown($raw['targetLanguage'] ?? null) ? $raw['targetLanguage'] : $query->target;

        if (! TranslatorLanguages::isKnown($source)) {
            throw new AiRejected('The input language was not detected.');
        }

        $meanings = collect(is_array($raw['meanings'] ?? null) ? $raw['meanings'] : [])
            ->filter(fn ($item) => is_array($item) && self::text($item['translation'] ?? null) !== '')
            ->take(4)
            ->map(function (array $item) {
                $exampleSource = self::text($item['exampleSource'] ?? null);
                $exampleTarget = self::text($item['exampleTarget'] ?? null);

                return [
                    'translation' => self::text($item['translation']),
                    'part_of_speech' => self::text($item['partOfSpeech'] ?? null),
                    'example' => $exampleSource !== '' && $exampleTarget !== '' ? ['source' => $exampleSource, 'target' => $exampleTarget] : null,
                ];
            })
            ->values()->all();

        // A word gets meanings, a sentence gets alternatives; never both.
        $alternatives = $meanings !== [] ? [] : collect(is_array($raw['alternatives'] ?? null) ? $raw['alternatives'] : [])
            ->filter(fn ($item) => is_array($item) && self::text($item['text'] ?? null) !== '')
            ->take(3)
            ->map(fn (array $item) => [
                'text' => self::text($item['text']),
                'register' => in_array($item['register'] ?? null, TranslationSchema::REGISTERS, true) ? $item['register'] : 'neutral',
            ])
            ->values()->all();

        $corrected = self::text($raw['correctedInput'] ?? null);

        return [
            'source_language' => $source,
            'target_language' => $target,
            'translation' => $translation,
            'reading' => self::text($raw['reading'] ?? null),
            'stressed' => $target === 'ru' ? self::text($raw['stressed'] ?? null) : '',
            'corrected_input' => self::isSpellingFix($query->text, $corrected) ? $corrected : '',
            'meanings' => $meanings,
            'alternatives' => $alternatives,
            'note' => self::text($raw['note'] ?? null),
        ];
    }

    /**
     * A single word joins the global dictionary with its meanings, so vocabulary features find it
     * too. Best effort: the learner already has the translation, a failure here must not cost it.
     */
    private function addToDictionary(TranslationQuery $query, array $result, User $user): void
    {
        $uzbekCyrillic = $query->uzScript === 'cyrillic' && in_array('uz', [$result['source_language'], $result['target_language']], true);

        if (! $query->isSingleWord() || $result['corrected_input'] !== '' || $uzbekCyrillic) {
            return;
        }

        $meanings = array_column($result['meanings'], 'translation') ?: [$result['translation']];

        try {
            $word = $this->dictionary->entry($result['source_language'], $query->text, author: $user);

            foreach ($meanings as $meaning) {
                if (mb_strlen($meaning) <= 160) {
                    $this->dictionary->translate($word, $result['target_language'], $meaning, $user);
                }
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    private static function text(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    /** A spelling fix changes a few letters; anything bigger is the model rewriting the learner's text. */
    private static function isSpellingFix(string $original, string $corrected): bool
    {
        if ($corrected === '' || $corrected === $original) {
            return false;
        }

        $a = mb_str_split(mb_strtolower($original));
        $b = mb_str_split(mb_strtolower($corrected));

        return self::editDistance($a, $b) / max(count($a), 1) <= 0.25;
    }

    /** @param list<string> $a @param list<string> $b */
    private static function editDistance(array $a, array $b): int
    {
        $previous = range(0, count($b));

        foreach ($a as $i => $charA) {
            $row = [$i + 1];
            foreach ($b as $j => $charB) {
                $row[$j + 1] = min($previous[$j + 1] + 1, $row[$j] + 1, $previous[$j] + ($charA === $charB ? 0 : 1));
            }
            $previous = $row;
        }

        return $previous[count($b)];
    }
}
