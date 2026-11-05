<?php

declare(strict_types=1);

namespace App\Modules\Progress\Services;

use App\Modules\Account\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class DailyStats
{
    /** @return array{daily_goal_minutes: int, today_minutes: int, today_xp: int, streak: int, xp: int} */
    public function for(User $user): array
    {
        $profile = $user->profile()->firstOrCreate([])->refresh();
        $today = CarbonImmutable::now($profile->timezone)->startOfDay();

        $todayRow = DB::table('daily_progress')
            ->where('user_id', $user->id)->where('day', $today->toDateString())
            ->first(['minutes', 'xp']);

        $streakAlive = $profile->last_active_on !== null
            && $profile->last_active_on->toImmutable()->gte($today->subDay());

        return [
            'daily_goal_minutes' => $profile->daily_goal_minutes,
            'today_minutes' => (int) ($todayRow->minutes ?? 0),
            'today_xp' => (int) ($todayRow->xp ?? 0),
            'streak' => $streakAlive ? $profile->streak : 0,
            'xp' => $profile->xp,
        ];
    }
}
