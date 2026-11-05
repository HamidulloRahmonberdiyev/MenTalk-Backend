<?php

declare(strict_types=1);

namespace App\Modules\Account\Actions;

use App\Modules\Account\Contracts\IdentityVerifier;
use App\Modules\Account\Events\UserRegistered;
use App\Modules\Account\Exceptions\AccountBlocked;
use App\Modules\Account\Models\User;
use App\Modules\Language\Enums\InterfaceLanguage;

final readonly class LoginWithGoogle
{
    private const TOKEN_DAYS = 90;

    public function __construct(private IdentityVerifier $verifier) {}

    /** @return array{0: User, 1: string} */
    public function __invoke(string $idToken, string $device, ?InterfaceLanguage $language = null): array
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

        // A new profile starts in the language the app was already showing; a returning user keeps theirs.
        $user->profile()->firstOrCreate([], array_filter(['ui_language' => $language]));

        if ($user->wasRecentlyCreated) {
            event(new UserRegistered($user));
        }

        return [$user, $user->createToken($device, ['*'], now()->addDays(self::TOKEN_DAYS))->plainTextToken];
    }
}
