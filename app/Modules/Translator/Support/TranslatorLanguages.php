<?php

declare(strict_types=1);

namespace App\Modules\Translator\Support;

/**
 * The languages the translator screen offers. Wider than the taught languages: a learner may
 * translate from Korean without studying it. Mirrors `TRANSLATE_LANGUAGES` in the app.
 */
final class TranslatorLanguages
{
    /** Code => English name, as used inside prompts. */
    public const NAMES = [
        'uz' => 'Uzbek',
        'ru' => 'Russian',
        'en' => 'English',
        'tr' => 'Turkish',
        'kk' => 'Kazakh',
        'ar' => 'Arabic',
        'de' => 'German',
        'ko' => 'Korean',
    ];

    public const AUTO = 'auto';

    /** @return list<string> */
    public static function codes(): array
    {
        return array_keys(self::NAMES);
    }

    public static function name(string $code): string
    {
        return self::NAMES[$code];
    }

    public static function isKnown(mixed $code): bool
    {
        return is_string($code) && array_key_exists($code, self::NAMES);
    }

    /** What the input is translated into when it is already in the target language (same rule as the app). */
    public static function fallbackFor(string $target): string
    {
        return $target === 'uz' ? 'ru' : 'uz';
    }
}
