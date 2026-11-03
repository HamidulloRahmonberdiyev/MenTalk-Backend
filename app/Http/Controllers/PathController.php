<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\LanguageCatalog;
use App\Enums\ConversationState;
use App\Http\Requests\ShowPathRequest;
use App\Models\Scenario;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

/**
 * The learner's path through the scenarios in one language: stages in order, each passed,
 * open (the first one not yet passed) or locked behind it.
 */
final class PathController extends Controller
{
    public function __invoke(ShowPathRequest $request, LanguageCatalog $languages): JsonResponse
    {
        $user = $request->user();
        $language = $request->language()
            ?? $user->profile()->value('target_language')
            ?? $languages->default()->code;

        $passScore = (int) config('path.pass_score');
        $starScores = config('path.stars');

        $scenarios = Scenario::query()
            ->where('active', true)
            ->whereHas('briefs', fn ($query) => $query->where('language', $language))
            ->orderBy('sort')
            ->pluck('id');

        $progress = $user->conversations()
            ->where('target_language', $language)
            ->where('state', ConversationState::Finished)
            ->whereIn('scenario_id', $scenarios)
            ->groupBy('scenario_id')
            ->selectRaw('scenario_id, max(score) as best_score, count(*) as attempts, max(ended_at) as last_played_at')
            ->get()
            ->keyBy('scenario_id');

        $openFound = false;
        $stages = $scenarios->values()->map(function (string $id, int $index) use ($progress, $passScore, $starScores, &$openFound): array {
            $row = $progress->get($id);
            $best = $row?->best_score === null ? null : (int) $row->best_score;
            $passed = $best !== null && $best >= $passScore;

            $status = match (true) {
                $passed => 'passed',
                ! $openFound => 'open',
                default => 'locked',
            };
            if ($status === 'open') {
                $openFound = true;
            }

            return [
                'stage' => $index + 1,
                'scenario_id' => $id,
                'status' => $status,
                'best_score' => $best,
                'attempts' => (int) ($row->attempts ?? 0),
                'stars' => $best === null ? 0 : count(array_filter($starScores, fn (int $min) => $best >= $min)),
                'last_played_at' => $row?->last_played_at === null ? null : CarbonImmutable::parse($row->last_played_at)->toIso8601String(),
            ];
        });

        return response()->json(['data' => [
            'language' => $language,
            'pass_score' => $passScore,
            'passed' => $stages->where('status', 'passed')->count(),
            'stages' => $stages->all(),
        ]]);
    }
}
