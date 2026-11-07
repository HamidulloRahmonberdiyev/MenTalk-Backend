<?php

declare(strict_types=1);

namespace App\Modules\Account\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * The language the learner grew up with. Separate from the interface language: a Kazakh or Tajik
 * speaker reads the app in Russian, yet makes the mistakes their own language leads them to.
 */
enum NativeLanguage: string implements HasLabel
{
    case Uzbek = 'uz';
    case Russian = 'ru';
    case English = 'en';
    case Kazakh = 'kk';
    case Kyrgyz = 'ky';
    case Tajik = 'tg';
    case Turkmen = 'tk';
    case Karakalpak = 'kaa';
    case Azerbaijani = 'az';
    case Turkish = 'tr';
    case Other = 'other';

    /** As named inside AI prompts. */
    public function englishName(): string
    {
        return match ($this) {
            self::Uzbek => 'Uzbek',
            self::Russian => 'Russian',
            self::English => 'English',
            self::Kazakh => 'Kazakh',
            self::Kyrgyz => 'Kyrgyz',
            self::Tajik => 'Tajik',
            self::Turkmen => 'Turkmen',
            self::Karakalpak => 'Karakalpak',
            self::Azerbaijani => 'Azerbaijani',
            self::Turkish => 'Turkish',
            self::Other => 'another language',
        };
    }

    public function getLabel(): string
    {
        return $this->englishName();
    }
}
