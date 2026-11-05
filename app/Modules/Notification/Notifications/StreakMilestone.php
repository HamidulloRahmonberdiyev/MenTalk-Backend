<?php

declare(strict_types=1);

namespace App\Modules\Notification\Notifications;

use App\Modules\Notification\DTO\NotificationMessage;
use App\Modules\Notification\Enums\NotificationKind;
use App\Modules\Notification\Enums\NotificationType;

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
