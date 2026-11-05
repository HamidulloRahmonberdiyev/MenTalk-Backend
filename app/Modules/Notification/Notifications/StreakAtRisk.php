<?php

declare(strict_types=1);

namespace App\Modules\Notification\Notifications;

use App\Modules\Notification\DTO\NotificationMessage;
use App\Modules\Notification\Enums\NotificationAction;
use App\Modules\Notification\Enums\NotificationKind;
use App\Modules\Notification\Enums\NotificationType;

/** Practised yesterday but not yet today: the streak ends at midnight. */
final class StreakAtRisk extends InboxNotification
{
    public function __construct(public readonly int $streak)
    {
        parent::__construct();
    }

    public function type(): NotificationType
    {
        return NotificationType::StreakAtRisk;
    }

    public function message(): NotificationMessage
    {
        return NotificationMessage::translated(NotificationKind::Streak, 'streak_at_risk', ['n' => $this->streak], NotificationAction::Scenarios);
    }
}
