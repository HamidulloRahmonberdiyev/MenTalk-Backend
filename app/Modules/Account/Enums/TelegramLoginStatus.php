<?php

declare(strict_types=1);

namespace App\Modules\Account\Enums;

/**
 * Pending → Opened (the bot asked for the phone number) → Approved (the person shared it) → Completed
 * (the app took its session). Declined ends it from the bot; an unused one simply expires.
 */
enum TelegramLoginStatus: string
{
    case Pending = 'pending';
    case Opened = 'opened';
    case Approved = 'approved';
    case Declined = 'declined';
    case Completed = 'completed';

    public function isWaiting(): bool
    {
        return $this === self::Pending || $this === self::Opened;
    }

    /** The person said yes in Telegram, whether or not the app has collected its session yet. */
    public function wasApproved(): bool
    {
        return $this === self::Approved || $this === self::Completed;
    }
}
