<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class StatsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->profile()->firstOrCreate([])->refresh();
        $today = CarbonImmutable::now($profile->timezone)->startOfDay();

        $todayRow = DB::table('daily_progress')
            ->where('user_id', $user->id)->where('day', $today->toDateString())
            ->first(['minutes', 'xp']);

        $streakAlive = $profile->last_active_on !== null
            && $profile->last_active_on->toImmutable()->gte($today->subDay());

        return response()->json(['data' => [
            'daily_goal_minutes' => $profile->daily_goal_minutes,
            'today_minutes' => (int) ($todayRow->minutes ?? 0),
            'today_xp' => (int) ($todayRow->xp ?? 0),
            'streak' => $streakAlive ? $profile->streak : 0,
            'xp' => $profile->xp,
        ]]);
    }
}
