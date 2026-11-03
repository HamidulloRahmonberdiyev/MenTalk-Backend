<?php

declare(strict_types=1);

namespace App\Notifications;

use App\DTO\NotificationMessage;
use App\Enums\NotificationKind;
use App\Enums\NotificationType;

final class StreakMilestone extends InboxNotification
{
    public function __construct(public readonly int $days)
    {
        parent::__construct();
    }

    public function type(): NotificationType
    {
        return NotificationType::StreakMilestone;
    }

    public function message(): NotificationMessage
    {
        return NotificationMessage::translated(NotificationKind::Achievement, 'streak_milestone', ['n' => $this->days]);
    }
}
