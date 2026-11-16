<?php

declare(strict_types=1);

namespace App\Modules\Account\Enums;

/**
 * Pending → Opened (the bot showed the prompt) → Approved (tapped "confirm") → Completed (the app took
 * its session). Declined ends it from the bot; an unused one simply expires.
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
}
