<?php

declare(strict_types=1);

namespace App\Modules\Pronunciation\Models;

use App\Modules\Pronunciation\Enums\PhraseLevel;
use Illuminate\Database\Eloquent\Model;
use Normalizer;

/**
 * A line to read aloud, shared by every learner of its language. The pool starts with hand-picked
 * lines and grows with AI-written batches only when a learner has read everything in it.
 * Which learner saw which line lives in `pronunciation_phrase_views`.
 */
final class Phrase extends Model
{
    protected $table = 'pronunciation_phrases';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['level' => PhraseLevel::class, 'active' => 'boolean'];
    }

    /**
     * Two lines that differ only in case, punctuation or spacing are the same line: the unique key
     * on (language, fingerprint) keeps the AI from adding one twice.
     */
    public static function fingerprint(string $text): string
    {
        $text = Normalizer::normalize($text, Normalizer::FORM_C) ?: $text;
        $text = mb_strtolower(str_replace('ё', 'е', $text));
        $text = (string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);

        return hash('sha256', trim($text));
    }
}
