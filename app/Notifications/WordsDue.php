<?php

declare(strict_types=1);

namespace App\Notifications;

use App\DTO\NotificationMessage;
use App\Enums\NotificationAction;
use App\Enums\NotificationKind;
use App\Enums\NotificationType;

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
