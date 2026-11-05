<?php

declare(strict_types=1);

namespace App\Modules\Notification\Jobs;

use App\Modules\Notification\Services\ExpoPushClient;
use App\Modules\Notification\Services\PushSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Asks how sent pushes fared. An accepted ticket only means Expo took the message: dead devices
 * and rejected Firebase credentials show up here, and Expo expects us to stop sending to the former.
 */
final class CheckPushReceipts implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 300;

    /** @param  array<string, string>  $tickets  push token by ticket id */
    public function __construct(public readonly array $tickets) {}

    public function handle(ExpoPushClient $expo): void
    {
        foreach ($expo->receipts(array_map(strval(...), array_keys($this->tickets))) as $ticketId => $receipt) {
            if (($receipt['status'] ?? null) !== 'ok' && isset($this->tickets[$ticketId])) {
                PushSender::reportFailure($this->tickets[$ticketId], $receipt);
            }
        }
    }
}
