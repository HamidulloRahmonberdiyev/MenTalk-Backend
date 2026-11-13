<?php

declare(strict_types=1);

namespace App\Modules\Progress\Services;

use App\Modules\Account\Models\User;
use App\Modules\Progress\Enums\LeaderboardPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Learners ranked by XP. Ties go to whoever signed up first (ULIDs sort by creation time), so every
 * learner has one stable place. Blocked and deleted accounts are left off, and so is anyone with no XP yet.
 */
final class Leaderboard
{
    public const MAX_LIMIT = 50;

    /**
     * @return array{
     *     period: string,
     *     since: string|null,
     *     top: list<array{rank: int, name: string, avatar_url: string|null, xp: int, is_me: bool}>,
     *     me: array{rank: int|null, xp: int},
     * }
     */
    public function for(User $user, LeaderboardPeriod $period, int $limit): array
    {
        $since = $period === LeaderboardPeriod::Week ? $this->weekStart($user) : null;
        // first(), not value(): value() would replace the select and drop the SUM.
        $myXp = (int) ($this->scores($period, $since)->where('s.user_id', $user->id)->first()?->total ?? 0);

        return [
            'period' => $period->value,
            'since' => $since,
            'top' => $this->top($user, $period, $since, $limit),
            'me' => ['rank' => $myXp > 0 ? $this->rank($user, $period, $since, $myXp) : null, 'xp' => $myXp],
        ];
    }

    /** Monday of the learner's current week in their own time zone, which is how `daily_progress` days are keyed. */
    private function weekStart(User $user): string
    {
        $timezone = $user->profile()->firstOrCreate([])->refresh()->timezone;

        return CarbonImmutable::now($timezone)->startOfWeek()->toDateString();
    }

    /** One row per eligible learner: `user_id` and their `total` XP for the period. */
    private function scores(LeaderboardPeriod $period, ?string $since): Builder
    {
        $query = $period === LeaderboardPeriod::Week
            ? DB::table('daily_progress as s')
                ->where('s.day', '>=', $since)
                ->groupBy('s.user_id')
                ->select('s.user_id')
                ->selectRaw('SUM(s.xp) as total')
            : DB::table('learner_profiles as s')->select('s.user_id', 's.xp as total');

        return $query->join('users as u', 'u.id', '=', 's.user_id')->whereNull('u.deleted_at')->whereNull('u.blocked_at');
    }

    /** @return list<array{rank: int, name: string, avatar_url: string|null, xp: int, is_me: bool}> */
    private function top(User $user, LeaderboardPeriod $period, ?string $since, int $limit): array
    {
        $rows = DB::query()->fromSub($this->scores($period, $since), 'scores')
            ->where('total', '>', 0)
            ->orderByDesc('total')
            ->orderBy('user_id')
            ->limit($limit)
            ->get(['user_id', 'total']);

        $users = User::query()
            ->whereKey($rows->pluck('user_id')->all())
            ->get(['id', 'name', 'avatar_path', 'google_avatar_url'])
            ->keyBy('id');

        return $rows->values()->map(fn (object $row, int $index): array => [
            'rank' => $index + 1,
            'name' => self::publicName((string) $users[$row->user_id]->name),
            'avatar_url' => $users[$row->user_id]->avatar_url,
            'xp' => (int) $row->total,
            'is_me' => $row->user_id === $user->id,
        ])->all();
    }

    private function rank(User $user, LeaderboardPeriod $period, ?string $since, int $xp): int
    {
        $ahead = DB::query()->fromSub($this->scores($period, $since), 'scores')
            ->where(fn (Builder $query) => $query
                ->where('total', '>', $xp)
                ->orWhere(fn (Builder $tie) => $tie->where('total', $xp)->where('user_id', '<', $user->id)))
            ->count();

        return $ahead + 1;
    }

    /** "Hamidullo Rahmonberdiyev" is shown as "Hamidullo R.": other learners never see a full surname. */
    private static function publicName(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return match (count($parts)) {
            0 => '',
            1 => $parts[0],
            default => $parts[0].' '.mb_strtoupper(mb_substr($parts[1], 0, 1)).'.',
        };
    }
}
