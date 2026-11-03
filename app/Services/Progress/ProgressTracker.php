<?php

declare(strict_types=1);

namespace App\Services\Progress;

use App\Models\LearnerProfile;
use App\Models\User;
use App\Notifications\StreakMilestone;
use App\Support\Streaks;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class ProgressTracker
{
    public function record(User $user, int $minutes, int $xp): void
    {
        $timezone = $user->profile()->firstOrCreate([])->refresh()->timezone;
        $today = CarbonImmutable::now($timezone)->startOfDay();

        DB::transaction(function () use ($user, $today, $minutes, $xp) {
            // Locked: two activities finishing at once must not both read the old streak (and both congratulate).
            $profile = LearnerProfile::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $day = DB::table('daily_progress')->where('user_id', $user->id)->where('day', $today->toDateString());

            DB::table('daily_progress')->insertOrIgnore([
                'user_id' => $user->id, 'day' => $today->toDateString(), 'minutes' => 0, 'xp' => 0,
            ]);
            $day->increment('minutes', $minutes, ['xp' => DB::raw("xp + {$xp}")]);

            $streak = Streaks::next($profile->streak, $profile->last_active_on?->toImmutable(), $today);

            // Only on the day the streak grows to a milestone; queued until this transaction commits.
            if ($streak !== $profile->streak && Streaks::isMilestone($streak)) {
                $user->notify(new StreakMilestone($streak));
            }

            $profile->update([
                'xp' => $profile->xp + $xp,
                'streak' => $streak,
                'last_active_on' => $today->toDateString(),
            ]);
        });
    }
}
