<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DTO\NotificationMessage;
use App\Services\Push\PushSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** One slice of an announcement's pushes, retried on its own if Expo is unreachable. */
final class SendPush implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    /** @param  array<string, string>  $recipients  notification id by user id */
    public function __construct(public readonly array $recipients, public readonly NotificationMessage $message) {}

    public function handle(PushSender $sender): void
    {
        $sender->send($this->recipients, $this->message);
    }
}
