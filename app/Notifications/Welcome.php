<?php

declare(strict_types=1);

namespace App\Notifications;

use App\DTO\NotificationMessage;
use App\Enums\NotificationAction;
use App\Enums\NotificationKind;
use App\Enums\NotificationType;

final class Welcome extends InboxNotification
{
    public function type(): NotificationType
    {
        return NotificationType::Welcome;
    }

    public function message(): NotificationMessage
    {
        return NotificationMessage::translated(NotificationKind::Lesson, 'welcome', action: NotificationAction::Scenarios);
    }
}
