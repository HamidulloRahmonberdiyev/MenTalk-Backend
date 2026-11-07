<?php

declare(strict_types=1);

namespace App\Modules\Certificate\Services;

use App\Modules\Account\Enums\LevelSource;
use App\Modules\Account\Models\User;
use App\Modules\Certificate\DTO\CertificateStanding;
use App\Modules\Conversation\Enums\ConversationState;
use App\Modules\Conversation\Models\Conversation;
use App\Modules\Vocabulary\Models\UserWord;

/**
 * Works out a learner's level and scores from what they have actually done in the app: the
 * placement test, rated conversations and their word list. A level picked by hand proves nothing
 * and is ignored.
 */
final readonly class CertificateAssessment
{
    public function __invoke(User $user): CertificateStanding
    {
        $profile = $user->profile()->firstOrCreate([]);
        $language = (string) $profile->target_language;
        $placed = $profile->level_source === LevelSource::Placement && $profile->placement_score !== null && $profile->level !== null;

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

        $conversations = (clone $rated)->count();
        // Soft-deleted rows are words the learner removed: they no longer count.
        $words = UserWord::query()
            ->where('user_id', $user->id)
            ->whereHas('word', fn ($word) => $word->where('language', $language))
            ->count();

        $talk = $results->isEmpty() ? null : [
            'speaking' => (float) $results->avg('speech'),
            'vocabulary' => (float) $results->avg('vocabulary'),
            'grammar' => (float) $results->avg('grammar'),
        ];
        $placementScore = $placed ? (int) $profile->placement_score : null;

        $level = LevelEstimate::fromConfig()(
            placement: $placed ? $profile->level : null,
            conversations: $conversations,
            conversationOverall: $talk === null ? null : array_sum($talk) / 3,
            words: $words,
        );

        return new CertificateStanding(
            language: $language,
            level: $level,
            placementScore: $placementScore,
            conversations: $conversations,
            requiredConversations: config('certificate.min_conversations'),
            words: $words,
            scores: $level === null ? null : self::scores($talk, $words, $placementScore),
        );
    }

    /**
     * Speaking and grammar only come from conversations. Vocabulary falls back to the size of the
     * word list. Overall averages what was measured, or is the placement result when that is all.
     *
     * @return array{speaking: ?float, vocabulary: ?float, grammar: ?float, overall: float}
     */
    private static function scores(?array $talk, int $words, ?int $placementScore): array
    {
        $vocabulary = $talk['vocabulary'] ?? ($words > 0 ? self::vocabularyFromWords($words) : null);
        $measured = array_filter([$talk['speaking'] ?? null, $vocabulary, $talk['grammar'] ?? null], fn ($score) => $score !== null);
        $overall = $measured !== [] ? array_sum($measured) / count($measured) : ($placementScore ?? 0) / 10;

        $round = fn (?float $score) => $score === null ? null : round(min($score, 10), 1);

        return [
            'speaking' => $round($talk['speaking'] ?? null),
            'vocabulary' => $round($vocabulary),
            'grammar' => $round($talk['grammar'] ?? null),
            'overall' => (float) $round($overall),
        ];
    }

    /** 0-10 on a log scale: the first hundred words count for much more than the next hundred. */
    private static function vocabularyFromWords(int $words): float
    {
        return 10 * log(1 + $words) / log(1 + config('certificate.words_for_full_score'));
    }
}
