<?php

declare(strict_types=1);

namespace App\Modules\Notification\Notifications;

use App\Modules\Notification\DTO\NotificationMessage;
use App\Modules\Notification\Enums\NotificationAction;
use App\Modules\Notification\Enums\NotificationKind;
use App\Modules\Notification\Enums\NotificationType;

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
