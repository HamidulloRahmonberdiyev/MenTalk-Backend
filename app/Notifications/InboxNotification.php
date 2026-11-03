<?php

declare(strict_types=1);

namespace App\Notifications;

use App\DTO\NotificationMessage;
use App\Enums\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * A message for the learner's in-app inbox. Queued and sent only after the surrounding
 * transaction commits, so a rolled-back action never leaves a notification behind and the
 * request that triggered it does not wait for the insert.
 *
 * To add push later: return 'expo' (or 'fcm') from `via()` next to 'database'.
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
        return ['database'];
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
