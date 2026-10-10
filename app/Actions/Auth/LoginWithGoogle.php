<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Contracts\IdentityVerifier;
use App\Models\User;

final readonly class LoginWithGoogle
{
    private const TOKEN_DAYS = 90;

    public function __construct(private IdentityVerifier $verifier) {}

    /** @return array{0: User, 1: string} */
    public function __invoke(string $idToken, string $device): array
    {
        $identity = $this->verifier->verify($idToken);

        $user = User::withTrashed()->updateOrCreate(
            ['google_id' => $identity->subject],
            ['email' => $identity->email, 'name' => $identity->name],
        );

        if ($user->trashed()) {
            $user->restore();
        }

        $user->profile()->firstOrCreate([]);

        return [$user, $user->createToken($device, ['*'], now()->addDays(self::TOKEN_DAYS))->plainTextToken];
    }
}
