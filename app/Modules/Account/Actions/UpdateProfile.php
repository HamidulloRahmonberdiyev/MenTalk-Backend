<?php

declare(strict_types=1);

namespace App\Modules\Account\Actions;

use App\Modules\Account\Enums\LevelSource;
use App\Modules\Account\Models\User;

final readonly class UpdateProfile
{
    /**
     * Applies validated profile edits. A placement result is stamped with the time it was taken; a
     * level changed without a source was picked by hand, so the earlier test result no longer applies.
     */
    public function __invoke(User $user, array $data): User
    {
        if (isset($data['name'])) {
            $user->update(['name' => $data['name']]);
        }

        $profile = $user->profile()->firstOrCreate([]);
        $changes = collect($data)->except('name')->all();

        $source = $changes['level_source'] ?? null;
        $handPicked = $source === null
            && array_key_exists('level', $changes)
            && $changes['level'] !== $profile->level?->value;

        if ($source === LevelSource::Placement->value) {
            $changes['placed_at'] = now();
        } elseif ($source === LevelSource::Self->value || $handPicked) {
            $changes += ['level_source' => LevelSource::Self->value];
            $changes['placement_score'] = null;
            $changes['placed_at'] = null;
        }

        $profile->update($changes);

        return $user->load('profile');
    }
}
