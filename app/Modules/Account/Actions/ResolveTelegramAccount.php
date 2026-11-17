<?php

declare(strict_types=1);

namespace App\Modules\Account\Actions;

use App\Modules\Account\DTO\TelegramContact;
use App\Modules\Account\DTO\TelegramUser;
use App\Modules\Account\Events\UserRegistered;
use App\Modules\Account\Exceptions\AccountBlocked;
use App\Modules\Account\Models\User;
use App\Modules\Language\Enums\InterfaceLanguage;
use InvalidArgumentException;

/** The account behind a Telegram user: found by Telegram id, or created on the first sign-in. */
final readonly class ResolveTelegramAccount
{
    public function __construct(private SyncTelegramAvatar $syncAvatar) {}

    /**
     * @param  TelegramContact  $contact  the person's own number, shared with the bot's button
     *
     * @throws AccountBlocked
     */
    public function __invoke(TelegramUser $telegram, TelegramContact $contact, ?InterfaceLanguage $language): User
    {
        // Only a number Telegram verified as this person's may ever be stored on their account.
        if (! $contact->belongsTo($telegram)) {
            throw new InvalidArgumentException('The contact is not the Telegram user\'s own number.');
        }

        // Telegram only seeds the name: once the user edits it, signing in again must not undo that.
        $user = User::withTrashed()->firstOrNew(['telegram_id' => $telegram->id], ['name' => $telegram->displayName()]);

        if ($user->isBlocked()) {
            throw new AccountBlocked;
        }

        // The number follows the Telegram account: if the person changed it there, it changes here too.
        $user->fill(['telegram_username' => $telegram->username])->forceFill(['phone' => $contact->phone])->save();

        if ($user->trashed()) {
            $user->restore();
        }

        // A new profile starts in the language the app was showing; a returning user keeps theirs.
        $user->profile()->firstOrCreate([], array_filter(['ui_language' => $language]));

        if ($user->wasRecentlyCreated) {
            event(new UserRegistered($user));
        }

        // A missing photo must never cost the person their sign-in.
        rescue(fn () => ($this->syncAvatar)($user));

        return $user;
    }
}
