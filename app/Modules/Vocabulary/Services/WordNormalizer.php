<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Services;

use Normalizer;

/**
 * The key a word is matched on (`words.normalized`): spellings a learner would consider the same
 * word collapse into one. The displayed `text` keeps the original spelling.
 */
final class WordNormalizer
{
    /** Harakat, Quranic marks, superscript alif and tatweel. */
    private const ARABIC_MARKS = '/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\x{0640}]/u';

    private const ARABIC_ALIFS = ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا'];

    public function normalize(string $text, string $language): string
    {
        // NFC first: the same letter typed on two keyboards can arrive composed or decomposed.
        $text = Normalizer::normalize($text, Normalizer::FORM_C) ?: $text;
        $text = mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $text)));

        return match ($language) {
            'ar' => strtr((string) preg_replace(self::ARABIC_MARKS, '', $text), self::ARABIC_ALIFS),
            // Stress marks (молоко́) are a reading aid, not part of the spelling.
            'ru' => str_replace(['ё', "\u{0301}"], ['е', ''], $text),
            default => $text,
        };
    }
}
