<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Contracts\IdentityVerifier;
use App\Exceptions\AccountBlocked;
use App\Models\User;

final readonly class LoginWithGoogle
{
    private const TOKEN_DAYS = 90;

    public function __construct(private IdentityVerifier $verifier) {}

    /** @return array{0: User, 1: string} */
    public function __invoke(string $idToken, string $device): array
    {
        $identity = $this->verifier->verify($idToken);

        // Google only seeds the name: once the user edits it, signing in again must not undo that.
        $user = User::withTrashed()->firstOrNew(['google_id' => $identity->subject], ['name' => $identity->name]);
        $user->fill(['email' => $identity->email, 'google_avatar_url' => $identity->picture])->save();

        if ($user->isBlocked()) {
            throw new AccountBlocked;
        }

        if ($user->trashed()) {
            $user->restore();
        }

        $user->profile()->firstOrCreate([]);

        return [$user, $user->createToken($device, ['*'], now()->addDays(self::TOKEN_DAYS))->plainTextToken];
    }
}
