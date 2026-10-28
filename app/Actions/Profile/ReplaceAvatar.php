<?php

declare(strict_types=1);

namespace App\Actions\Profile;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final readonly class ReplaceAvatar
{
    /** Stores the new photo (or none) and only then deletes the previous file, so a failed save never leaves the user without one. */
    public function __invoke(User $user, ?UploadedFile $photo): User
    {
        $previous = $user->avatar_path;

        $user->forceFill(['avatar_path' => $photo?->store($user->id, User::AVATAR_DISK)])->save();

        if ($previous !== null) {
            Storage::disk(User::AVATAR_DISK)->delete($previous);
        }

        return $user;
    }
}
