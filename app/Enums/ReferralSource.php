<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Where the learner first heard of the app. */
enum ReferralSource: string implements HasLabel
{
    case Instagram = 'instagram';
    case Telegram = 'telegram';
    case TikTok = 'tiktok';
    case YouTube = 'youtube';
    case Friend = 'friend';
    case Search = 'search';
    case Store = 'store';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Instagram => 'Instagram',
            self::Telegram => 'Telegram',
            self::TikTok => 'TikTok',
            self::YouTube => 'YouTube',
            self::Friend => 'Do\'stlar',
            self::Search => 'Google',
            self::Store => 'App Store / Play Market',
            self::Other => 'Boshqa',
        };
    }

    public function chartColor(): string
    {
        return match ($this) {
            self::Instagram => '#e4405f',
            self::Telegram => '#26a5e4',
            self::TikTok => '#111827',
            self::YouTube => '#ff0000',
            self::Friend => '#22b573',
            self::Search => '#4285f4',
            self::Store => '#7b4fe0',
            self::Other => '#9ca3af',
        };
    }
}
