<?php

declare(strict_types=1);

namespace App\Enums;

/** The language the app's own texts are shown in. Explanations and glosses are written in it. */
enum InterfaceLanguage: string
{
    case Uzbek = 'uz';
    case Russian = 'ru';
    case English = 'en';

    /** As named inside AI prompts. */
    public function englishName(): string
    {
        return match ($this) {
            self::Uzbek => 'Uzbek',
            self::Russian => 'Russian',
            self::English => 'English',
        };
    }
}
