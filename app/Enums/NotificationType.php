<?php

declare(strict_types=1);

namespace App\Enums;

/** The `type` column of `notifications`: which event wrote the row. */
enum NotificationType: string
{
    case Welcome = 'welcome';
    case StreakMilestone = 'streak_milestone';
    case StreakAtRisk = 'streak_at_risk';
    case WordsDue = 'words_due';
    case Announcement = 'announcement';

    /** The daily nudges; a learner gets at most one of these a day. */
    public const REMINDERS = [self::StreakAtRisk, self::WordsDue];

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
