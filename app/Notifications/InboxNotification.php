<?php

declare(strict_types=1);

namespace App\Notifications;

use App\DTO\NotificationMessage;
use App\Enums\NotificationType;
use App\Notifications\Channels\ExpoPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * A message for the learner's in-app inbox. Queued and sent only after the surrounding
 * transaction commits, so a rolled-back action never leaves a notification behind and the
 * request that triggered it does not wait for the insert.
 *
 * Everything but the daily reminders also goes to the learner's phone as a push. Reminders stay in
 * the inbox: the app schedules its own on the device, and a second one from here would be a duplicate.
 */
abstract class InboxNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->afterCommit();
    }

    abstract public function type(): NotificationType;

    abstract public function message(): NotificationMessage;

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return in_array($this->type(), NotificationType::REMINDERS, true) ? ['database'] : ['database', ExpoPushChannel::class];
    }

    public function databaseType(object $notifiable): string
    {
        return $this->type()->value;
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->message()->toArray();
    }
}
