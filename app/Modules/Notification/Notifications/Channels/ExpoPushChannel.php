<?php

declare(strict_types=1);

namespace App\Modules\Notification\Notifications\Channels;

use App\Modules\Account\Models\User;
use App\Modules\Notification\Notifications\InboxNotification;
use App\Modules\Notification\Services\PushSender;

final readonly class ExpoPushChannel
{
    public function __construct(private PushSender $sender) {}

    public function send(User $notifiable, InboxNotification $notification): void
    {
        $this->sender->send([$notifiable->getKey() => $notification->id], $notification->message());
    }
}
