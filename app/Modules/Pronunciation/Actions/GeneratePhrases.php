<?php

declare(strict_types=1);

namespace App\Modules\Pronunciation\Actions;

use App\Modules\Ai\Contracts\AiClient;
use App\Modules\Ai\DTO\AiRequest;
use App\Modules\Ai\Services\PromptRenderer;
use App\Modules\Language\DTO\TutorLanguage;
use App\Modules\Pronunciation\Enums\PhraseLevel;
use App\Modules\Pronunciation\Models\Phrase;
use Illuminate\Support\Facades\DB;

/**
 * Adds one AI-written batch to the shared pool. One call yields a whole batch, and every learner of
 * the language reads from it afterwards, so the AI is asked rarely and never for a single line.
 */
final readonly class GeneratePhrases
{
    public const BATCH = 15;

    /** Recent lines shown to the model so it does not repeat them; enough to steer, few enough to stay cheap. */
    private const AVOID = 40;

    private const SCHEMA = [
        'type' => 'OBJECT',
        'properties' => ['phrases' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']]],
        'required' => ['phrases'],
    ];

    public function __construct(private AiClient $ai, private PromptRenderer $prompts) {}

    /** @return int lines actually added: duplicates and malformed lines are dropped */
    public function __invoke(TutorLanguage $language, PhraseLevel $level): int
    {
        $avoid = Phrase::query()->where(['language' => $language->code, 'level' => $level])
            ->latest('id')->limit(self::AVOID)->pluck('text');

        $answer = $this->ai->json(new AiRequest(
            system: $this->prompts->render('pronunciation.phrases', $language, [
                '{count}' => (string) self::BATCH,
                '{level_rule}' => $level->rule(),
                '{avoid}' => $avoid->isEmpty() ? '(none yet)' : $avoid->map(fn (string $text) => "- {$text}")->implode("\n"),
            ]),
            contents: [['role' => 'user', 'parts' => [['text' => 'Write '.self::BATCH.' new lines.']]]],
            schema: self::SCHEMA,
            temperature: 0.9,
        ));

        $now = now();
        $rows = collect(is_array($answer['phrases'] ?? null) ? $answer['phrases'] : [])
            ->filter(fn ($line) => is_string($line))
            ->map(fn (string $line) => trim((string) preg_replace('/\s+/u', ' ', $line)))
            ->filter(fn (string $line) => self::isUsable($line, $level))
            ->unique(fn (string $line) => Phrase::fingerprint($line))
            ->take(self::BATCH)
            ->map(fn (string $line) => [
                'language' => $language->code,
                'level' => $level->value,
                'text' => $line,
                'fingerprint' => Phrase::fingerprint($line),
                'source' => 'ai',
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()->all();

        // Lines already in the pool hit the unique key and are skipped.
        return $rows === [] ? 0 : DB::table('pronunciation_phrases')->insertOrIgnore($rows);
    }

    /** Readable aloud as written: no digits to guess a reading for, a length that fits the level. */
    private static function isUsable(string $line, PhraseLevel $level): bool
    {
        if (mb_strlen($line) < 2 || mb_strlen($line) > 200 || preg_match('/\d/u', $line) === 1) {
            return false;
        }

        [$min, $max] = $level->wordRange();
        $words = count(preg_split('/\s+/u', $line) ?: []);

        return $words >= $min && $words <= $max;
    }
}
