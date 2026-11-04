<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Models\User;
use App\Notifications\InboxNotification;
use App\Services\Push\PushSender;

final readonly class ExpoPushChannel
{
    public function __construct(private PushSender $sender) {}

    public function send(User $notifiable, InboxNotification $notification): void
    {
        $this->sender->send([$notifiable->getKey() => $notification->id], $notification->message());
    }
}
