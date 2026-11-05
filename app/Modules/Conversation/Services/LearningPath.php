<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Services;

use App\Modules\Account\Models\User;
use App\Modules\Conversation\Enums\ConversationState;
use App\Modules\Conversation\Models\Scenario;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * The learner's path through the scenarios in one language: stages in order, each passed,
 * open (the first one not yet passed) or locked behind it.
 */
final class LearningPath
{
    /** @return array{language: string, pass_score: int, passed: int, stages: list<array<string, mixed>>} */
    public function for(User $user, string $language): array
    {
        $passScore = (int) config('path.pass_score');
        $scenarios = $this->scenarios($language);
        $stages = $this->stages($scenarios, $this->attempts($user, $language, $scenarios), $passScore);

        return [
            'language' => $language,
            'pass_score' => $passScore,
            'passed' => $stages->where('status', 'passed')->count(),
            'stages' => $stages->all(),
        ];
    }

    /** @return Collection<int, string> */
    private function scenarios(string $language): Collection
    {
        return Scenario::query()
            ->where('active', true)
            ->whereHas('briefs', fn ($query) => $query->where('language', $language))
            ->orderBy('sort')
            ->pluck('id');
    }

    /** @param  Collection<int, string>  $scenarios */
    private function attempts(User $user, string $language, Collection $scenarios): Collection
    {
        return $user->conversations()
            ->where('target_language', $language)
            ->where('state', ConversationState::Finished)
            ->whereIn('scenario_id', $scenarios)
            ->groupBy('scenario_id')
            ->selectRaw('scenario_id, max(score) as best_score, count(*) as attempts, max(ended_at) as last_played_at')
            ->get()
            ->keyBy('scenario_id');
    }

    /** @param  Collection<int, string>  $scenarios */
    private function stages(Collection $scenarios, Collection $attempts, int $passScore): Collection
    {
        $starScores = config('path.stars');
        $openFound = false;

        return $scenarios->values()->map(function (string $id, int $index) use ($attempts, $passScore, $starScores, &$openFound): array {
            $row = $attempts->get($id);
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
    }
}
