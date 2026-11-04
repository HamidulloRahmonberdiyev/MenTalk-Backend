<?php

declare(strict_types=1);

namespace App\Services\Certificate;

use App\DTO\CertificateStanding;
use App\Enums\ConversationState;
use App\Enums\LevelSource;
use App\Models\Conversation;
use App\Models\User;

/** Works out a learner's level and scores from what they have actually done in the app. */
final readonly class CertificateAssessment
{
    public function __invoke(User $user): CertificateStanding
    {
        $profile = $user->profile()->firstOrCreate([]);
        $language = (string) $profile->target_language;

        $rated = Conversation::query()
            ->where('user_id', $user->id)
            ->where('target_language', $language)
            ->where('state', ConversationState::Finished)
            ->where('learner_turns', '>=', config('certificate.min_turns'))
            ->whereHas('result');

        $results = (clone $rated)
            ->with('result')
            ->latest('ended_at')
            ->limit(config('certificate.recent_conversations'))
            ->get()
            ->pluck('result');

        return new CertificateStanding(
            language: $language,
            level: $profile->level,
            placementScore: $profile->level_source === LevelSource::Placement ? $profile->placement_score : null,
            conversations: (clone $rated)->count(),
            requiredConversations: config('certificate.min_conversations'),
            scores: $results->isEmpty() ? null : self::scores($results->avg('speech'), $results->avg('vocabulary'), $results->avg('grammar')),
        );
    }

    /** @return array{speaking: float, vocabulary: float, grammar: float, overall: float} */
    private static function scores(float $speaking, float $vocabulary, float $grammar): array
    {
        return [
            'speaking' => round($speaking, 1),
            'vocabulary' => round($vocabulary, 1),
            'grammar' => round($grammar, 1),
            'overall' => round(($speaking + $vocabulary + $grammar) / 3, 1),
        ];
    }
}
