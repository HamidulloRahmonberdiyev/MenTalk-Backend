<?php

declare(strict_types=1);

namespace App\Modules\Notification\Notifications;

use App\Modules\Notification\DTO\NotificationMessage;
use App\Modules\Notification\Enums\NotificationAction;
use App\Modules\Notification\Enums\NotificationKind;
use App\Modules\Notification\Enums\NotificationType;

final class WordsDue extends InboxNotification
{
    public function __construct(public readonly int $count)
    {
        parent::__construct();
    }

    public function type(): NotificationType
    {
        return NotificationType::WordsDue;
    }

    public function message(): NotificationMessage
    {
        return NotificationMessage::translated(NotificationKind::Review, 'words_due', ['n' => $this->count], NotificationAction::Practice);
    }
}
