<?php

declare(strict_types=1);

namespace App\Modules\Account\Actions;

use App\Modules\Account\Enums\TelegramLoginStatus;
use App\Modules\Account\Exceptions\AccountBlocked;
use App\Modules\Account\Models\TelegramLogin;
use App\Modules\Account\Models\User;
use Illuminate\Support\Facades\DB;

/** What the polling app learns about its attempt; once approved, the session token, exactly once. */
final readonly class CheckTelegramLogin
{
    /**
     * @return array{status: 'pending'|'opened'|'declined'|'expired'}|array{status: 'approved', user: User, token: string}
     *
     * @throws AccountBlocked
     */
    public function __invoke(TelegramLogin $login): array
    {
        if ($login->status !== TelegramLoginStatus::Approved) {
            return ['status' => $this->waitingStatus($login)];
        }

        return DB::transaction(function () use ($login): array {
            // Two polls racing for the same approval: only the first one gets a token.
            $login = TelegramLogin::query()->lockForUpdate()->findOrFail($login->id);

            if ($login->status !== TelegramLoginStatus::Approved || $login->isExpired()) {
                return ['status' => $this->waitingStatus($login)];
            }

            $user = $login->user;

            if ($user === null || $user->isBlocked()) {
                throw new AccountBlocked;
            }

            $login->update(['status' => TelegramLoginStatus::Completed]);

            return ['status' => 'approved', 'user' => $user, 'token' => $user->issueApiToken($login->device)];
        });
    }

    /** @return 'pending'|'opened'|'declined'|'expired' */
    private function waitingStatus(TelegramLogin $login): string
    {
        return match (true) {
            $login->status === TelegramLoginStatus::Declined => 'declined',
            $login->isOpen() => $login->status === TelegramLoginStatus::Opened ? 'opened' : 'pending',
            // Expired, or already completed by another poll.
            default => 'expired',
        };
    }
}
