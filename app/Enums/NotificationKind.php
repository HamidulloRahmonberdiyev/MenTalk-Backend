<?php

declare(strict_types=1);

namespace App\Enums;

/** What a notification is about; the app picks its icon and colours from it. */
enum NotificationKind: string
{
    case Streak = 'streak';
    case Review = 'review';
    case Achievement = 'achievement';
    case Lesson = 'lesson';
    case Pronunciation = 'pronunciation';
    case News = 'news';

    public function label(): string
    {
        return match ($this) {
            self::Streak => 'Streak',
            self::Review => 'Takrorlash',
            self::Achievement => 'Yutuq',
            self::Lesson => 'Dars',
            self::Pronunciation => 'Talaffuz',
            self::News => 'Yangilik',
        };
    }
}
