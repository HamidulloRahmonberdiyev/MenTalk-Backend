<?php

declare(strict_types=1);

namespace App\Modules\Notification\Enums;

/**
 * Where tapping a notification leads. The server names a screen, never a path: the app maps each
 * name to its own route and ignores names it does not know, so old builds stay safe.
 */
enum NotificationAction: string
{
    case Scenarios = 'scenarios';
    case Words = 'words';
    case Practice = 'practice';
    case Pronunciation = 'pronunciation';
    case Translate = 'translate';

    /** Translation key of the button text. */
    public function labelKey(): string
    {
        return "notifications.action.{$this->value}";
    }

    public function label(): string
    {
        return match ($this) {
            self::Scenarios => 'Suhbatlar',
            self::Words => "So'zlar ro'yxati",
            self::Practice => "So'zlarni takrorlash",
            self::Pronunciation => 'Talaffuz',
            self::Translate => 'Tarjimon',
        };
    }
}
