<?php

declare(strict_types=1);

namespace App\Services\Progress;

use App\Models\User;
use App\Support\Streaks;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class ProgressTracker
{
    public function record(User $user, int $minutes, int $xp): void
    {
        $profile = $user->profile()->firstOrCreate([])->refresh();
        $today = CarbonImmutable::now($profile->timezone)->startOfDay();

        DB::transaction(function () use ($user, $profile, $today, $minutes, $xp) {
            $day = DB::table('daily_progress')->where('user_id', $user->id)->where('day', $today->toDateString());

            DB::table('daily_progress')->insertOrIgnore([
                'user_id' => $user->id, 'day' => $today->toDateString(), 'minutes' => 0, 'xp' => 0,
            ]);
            $day->increment('minutes', $minutes, ['xp' => DB::raw("xp + {$xp}")]);

            $profile->update([
                'xp' => $profile->xp + $xp,
                'streak' => Streaks::next($profile->streak, $profile->last_active_on?->toImmutable(), $today),
                'last_active_on' => $today->toDateString(),
            ]);
        });
    }
}
