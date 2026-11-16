<?php

declare(strict_types=1);

namespace App\Modules\Account\Actions;

use App\Modules\Account\Models\User;
use App\Modules\Account\Services\TelegramBot;
use Illuminate\Support\Facades\Storage;

/**
 * Copies the person's current Telegram profile photo to the avatars disk. A Telegram file link
 * contains the bot token, so the photo cannot be handed to the app by its URL.
 */
final readonly class SyncTelegramAvatar
{
    public function __construct(private TelegramBot $bot) {}

    public function __invoke(User $user): void
    {
        $photos = $this->bot->call('getUserProfilePhotos', ['user_id' => $user->telegram_id, 'limit' => 1], timeout: 5);
        $sizes = $photos['photos'][0] ?? [];

        // No photo, or hidden by the person's privacy settings: show none rather than an outdated one.
        if ($sizes === []) {
            $this->replace($user, null);

            return;
        }

        $largest = collect($sizes)->sortByDesc(fn (array $size): int => (int) $size['width'])->first();
        $path = "{$user->id}/telegram-{$largest['file_unique_id']}.jpg";

        if ($user->telegram_avatar_path === $path) {
            return;
        }

        $file = $this->bot->call('getFile', ['file_id' => $largest['file_id']], timeout: 5);
        Storage::disk(User::AVATAR_DISK)->put($path, $this->bot->download($file['file_path']));

        $this->replace($user, $path);
    }

    private function replace(User $user, ?string $path): void
    {
        $previous = $user->telegram_avatar_path;

        if ($previous === $path) {
            return;
        }

        $user->forceFill(['telegram_avatar_path' => $path])->save();

        if ($previous !== null) {
            Storage::disk(User::AVATAR_DISK)->delete($previous);
        }
    }
}
