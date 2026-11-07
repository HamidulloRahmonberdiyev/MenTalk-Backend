<?php

declare(strict_types=1);

namespace App\Modules\Mistakes\Support;

/**
 * How two corrections are recognised as the same mistake: case, punctuation and spacing do not
 * count, letters do (a wrong letter can be the very mistake, "е" for "ё" included).
 */
final class MistakeText
{
    public const MAX_LENGTH = 300;

    public static function clean(mixed $text): string
    {
        if (! is_string($text)) {
            return '';
        }

        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        return mb_strlen($text) > self::MAX_LENGTH ? rtrim(mb_substr($text, 0, self::MAX_LENGTH)) : $text;
    }

    public static function normalize(string $text): string
    {
        // Keeps letters, digits, combining marks (stress, Uzbek and Turkish diacritics) and the
        // apostrophes and hyphens that are part of words; everything else is punctuation.
        $text = (string) preg_replace("/[^\p{L}\p{N}\p{M}'ʼʻ‘’\-\s]+/u", ' ', mb_strtolower($text));

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /** True when the "correction" only changes case or punctuation: nothing worth practising. */
    public static function trivial(string $said, string $correct): bool
    {
        $said = self::normalize($said);

        return $said === '' || $said === self::normalize($correct);
    }

    public static function fingerprint(string $said, string $correct): string
    {
        return hash('sha256', self::normalize($said)."\n".self::normalize($correct));
    }
}
