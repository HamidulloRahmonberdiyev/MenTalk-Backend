<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class PurgeUserData implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $userId) {}

    public function handle(): void
    {
        $user = User::onlyTrashed()->find($this->userId);

        // Restored accounts (user logged in again within the grace period) are kept.
        $user?->forceDelete();
    }
}
