<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Services;

use App\Modules\Account\Models\User;
use App\Modules\Vocabulary\Models\Word;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/** The only way words enter the global dictionary, so a spelling never gets two entries. */
final readonly class Dictionary
{
    public function __construct(private WordNormalizer $normalizer) {}

    /**
     * The entry for a spelling, created on first use; safe when two requests add it at once. An
     * unknown part of speech reuses whichever entry the spelling already has.
     */
    public function entry(string $language, string $text, string $pos = '', ?User $author = null): Word
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));
        $normalized = $this->normalizer->normalize($text, $language);

        if ($normalized === '') {
            throw new InvalidArgumentException("Nothing is left of \"{$text}\" to match a word on.");
        }

        $known = ['language' => $language, 'normalized' => $normalized];

        if ($pos === '' && ($existing = Word::query()->where($known)->orderBy('pos')->first()) !== null) {
            return $existing;
        }

        return Word::query()->createOrFirst($known + ['pos' => $pos], ['text' => $text, 'created_by_user_id' => $author?->id]);
    }

    /** The entry for a meaning of `$word`, linked to it both ways. */
    public function translate(Word $word, string $language, string $text, ?User $author = null): Word
    {
        $translation = $this->entry($language, $text, author: $author);

        if (! $translation->is($word)) {
            DB::table('word_translations')->insertOrIgnore([
                ['word_id' => $word->id, 'translated_word_id' => $translation->id],
                ['word_id' => $translation->id, 'translated_word_id' => $word->id],
            ]);
        }

        return $translation;
    }
}
