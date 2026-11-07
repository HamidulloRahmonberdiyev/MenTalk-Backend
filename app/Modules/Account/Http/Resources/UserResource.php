<?php

declare(strict_types=1);

namespace App\Modules\Account\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->profile;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar_url' => $this->avatar_url,
            'avatar_custom' => $this->avatar_path !== null,
            'gender' => $profile?->gender?->value,
            'country' => $profile?->country,
            'birth_date' => $profile?->birth_date?->toDateString(),
            'level' => $profile?->level?->value,
            'level_source' => $profile?->level_source?->value,
            'placement_score' => $profile?->placement_score,
            'experience' => $profile?->experience?->value,
            'referral_source' => $profile?->referral_source?->value,
            'goals' => $profile?->goals ?? [],
            'focus' => $profile?->focus ?? [],
            'native_language' => $profile?->native_language?->value,
            'ui_language' => $profile?->ui_language?->value,
            'target_language' => $profile?->target_language,
            'timezone' => $profile?->timezone,
            'daily_goal_minutes' => $profile?->daily_goal_minutes,
            'xp' => $profile?->xp,
            'streak' => $profile?->streak,
        ];
    }
}
