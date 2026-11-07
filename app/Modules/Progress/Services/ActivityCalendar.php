<?php

declare(strict_types=1);

namespace App\Modules\Progress\Services;

use App\Modules\Account\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * One month of the learner's practice, day by day, in the learner's own time zone: minutes spoken,
 * XP earned in drills and the number of conversations, plus the streak figures shown above it.
 */
final class ActivityCalendar
{
    /**
     * @param  string|null  $month  `Y-m`; the current month when null
     * @return array{
     *     month: string, today: string, since: string|null, goal_minutes: int, streak: int, best_streak: int,
     *     days: list<array{date: string, minutes: int, xp: int, conversations: int, goal_met: bool}>,
     *     totals: array{active_days: int, minutes: int, xp: int, conversations: int}
     * }
     */
    public function month(User $user, ?string $month = null): array
    {
        $profile = $user->profile()->firstOrCreate([])->refresh();
        $zone = $profile->timezone ?: config('app.timezone');
        $today = CarbonImmutable::now($zone)->startOfDay();
        $start = $month ? CarbonImmutable::createFromFormat('!Y-m', $month, $zone) : $today->startOfMonth();
        $end = $start->endOfMonth();
        $goal = (int) $profile->daily_goal_minutes;

        $progress = DB::table('daily_progress')
            ->where('user_id', $user->id)
            ->whereBetween('day', [$start->toDateString(), $end->toDateString()])
            ->get(['day', 'minutes', 'xp'])
            ->keyBy(static fn (object $row) => substr((string) $row->day, 0, 10));

        // Stored in UTC; a talk at 01:00 in Tashkent belongs to that day, not the one before.
        $talks = DB::table('conversations')
            ->where('user_id', $user->id)
            ->where('learner_turns', '>', 0)
            ->whereBetween('started_at', [$start->utc(), $end->utc()])
            ->pluck('started_at')
            ->countBy(static fn ($at) => CarbonImmutable::parse($at, 'UTC')->setTimezone($zone)->toDateString());

        $days = $progress->keys()->merge($talks->keys())->unique()->sort()->values()
            ->map(static function (string $date) use ($progress, $talks, $goal): array {
                $minutes = (int) ($progress[$date]->minutes ?? 0);

                return [
                    'date' => $date,
                    'minutes' => $minutes,
                    'xp' => (int) ($progress[$date]->xp ?? 0),
                    'conversations' => (int) ($talks[$date] ?? 0),
                    'goal_met' => $goal > 0 && $minutes >= $goal,
                ];
            })
            ->filter(static fn (array $day) => $day['minutes'] + $day['xp'] + $day['conversations'] > 0)
            ->values();

        $streakAlive = $profile->last_active_on !== null && $profile->last_active_on->toImmutable()->gte($today->subDay());
        $active = $this->activeDays($user);

        return [
            'month' => $start->format('Y-m'),
            'today' => $today->toDateString(),
            'since' => $active[0] ?? null,
            'goal_minutes' => $goal,
            'streak' => $streakAlive ? (int) $profile->streak : 0,
            'best_streak' => max($this->longestRun($active), $streakAlive ? (int) $profile->streak : 0),
            'days' => $days->all(),
            'totals' => [
                'active_days' => $days->count(),
                'minutes' => (int) $days->sum('minutes'),
                'xp' => (int) $days->sum('xp'),
                'conversations' => (int) $days->sum('conversations'),
            ],
        ];
    }

    /** @return list<string> every day with any practice, oldest first */
    private function activeDays(User $user): array
    {
        return DB::table('daily_progress')
            ->where('user_id', $user->id)
            ->where(static fn ($query) => $query->where('minutes', '>', 0)->orWhere('xp', '>', 0))
            ->orderBy('day')
            ->pluck('day')
            ->map(static fn ($day) => substr((string) $day, 0, 10))
            ->all();
    }

    /** @param  list<string>  $days  sorted `Y-m-d` */
    private function longestRun(array $days): int
    {
        $best = 0;
        $run = 0;
        $previous = null;

        foreach ($days as $day) {
            $date = CarbonImmutable::parse($day);
            $run = $previous && $previous->addDay()->isSameDay($date) ? $run + 1 : 1;
            $best = max($best, $run);
            $previous = $date;
        }

        return $best;
    }
}
