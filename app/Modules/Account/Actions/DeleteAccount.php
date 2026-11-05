<?php

declare(strict_types=1);

namespace App\Modules\Account\Actions;

use App\Modules\Account\Jobs\PurgeUserData;
use App\Modules\Account\Models\User;

final readonly class DeleteAccount
{
    private const GRACE_DAYS = 30;

    public function __invoke(User $user): void
    {
        $user->tokens()->delete();
        $user->delete();

        PurgeUserData::dispatch($user->id)->delay(now()->addDays(self::GRACE_DAYS));
    }
}
