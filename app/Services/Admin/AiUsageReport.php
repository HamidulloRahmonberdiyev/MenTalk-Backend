<?php

declare(strict_types=1);

namespace App\Services\Admin;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Aggregates the ai_usage log for the dashboard. Results are cached briefly so refreshes stay cheap. */
final class AiUsageReport
{
    private const TTL_SECONDS = 60;

    /** @return array{requests: int, errors: int, error_rate: float, avg_ms: int, p95_ms: int} */
    public function lastDay(): array
    {
        return Cache::remember('admin:ai-usage:day', self::TTL_SECONDS, function () {
            $since = CarbonImmutable::now()->subDay();
            $base = fn () => DB::table('ai_usage')->where('created_at', '>=', $since);

            $requests = $base()->count();
            $errors = $base()->where('status', '>=', 400)->count();

            return [
                'requests' => $requests,
                'errors' => $errors,
                'error_rate' => $requests > 0 ? round($errors / $requests * 100, 1) : 0.0,
                'avg_ms' => (int) round((float) $base()->avg('latency_ms')),
                'p95_ms' => $requests > 0
                    ? (int) $base()->orderBy('latency_ms')->offset((int) floor(($requests - 1) * 0.95))->limit(1)->value('latency_ms')
                    : 0,
            ];
        });
    }

    /** @return array{labels: list<string>, requests: list<int>, errors: list<int>} */
    public function daily(int $days = 14): array
    {
        return Cache::remember("admin:ai-usage:daily:{$days}", self::TTL_SECONDS, function () use ($days) {
            $start = CarbonImmutable::today()->subDays($days - 1);

            $rows = DB::table('ai_usage')
                ->where('created_at', '>=', $start)
                ->selectRaw('date(created_at) as day, count(*) as total, sum(case when status >= 400 then 1 else 0 end) as failed')
                ->groupBy('day')
                ->get()
                ->keyBy('day');

            $labels = $requests = $errors = [];

            for ($i = 0; $i < $days; $i++) {
                $day = $start->addDays($i);
                $row = $rows->get($day->toDateString());

                $labels[] = $day->format('d.m');
                $requests[] = (int) ($row->total ?? 0);
                $errors[] = (int) ($row->failed ?? 0);
            }

            return compact('labels', 'requests', 'errors');
        });
    }
}
