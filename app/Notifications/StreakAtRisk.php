<?php

declare(strict_types=1);

namespace App\Notifications;

use App\DTO\NotificationMessage;
use App\Enums\NotificationAction;
use App\Enums\NotificationKind;
use App\Enums\NotificationType;

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
