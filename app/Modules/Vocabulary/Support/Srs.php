<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Support;

use App\Modules\Vocabulary\Enums\ReviewRating;
use App\Modules\Vocabulary\Enums\WordStatus;
use Carbon\CarbonImmutable;

/**
 * SM-2 spaced repetition. The mobile app runs the same rules offline (`srs.ts`); change both together.
 */
final class Srs
{
    public const START_EASE = 2.5;

    private const MIN_EASE = 1.3;

    private const MAX_INTERVAL_DAYS = 365;

    /** From this interval on a word counts as known. */
    private const KNOWN_AFTER_DAYS = 21;

    private const RETRY_MINUTES = 10;

    public static function review(ReviewState $state, ReviewRating $rating, CarbonImmutable $now): ReviewState
    {
        if ($rating === ReviewRating::Again) {
            return new ReviewState(
                WordStatus::Learning,
                self::ease($state->ease - 0.2),
                0,
                0,
                $state->lapses + 1,
                $now->addMinutes(self::RETRY_MINUTES),
            );
        }

        // Extra practice is welcome, but only a review that was due counts as spaced repetition.
        if ($state->dueAt->isAfter($now)) {
            return $state;
        }

        $ease = self::ease($state->ease + match ($rating) {
            ReviewRating::Hard => -0.15,
            ReviewRating::Easy => 0.15,
            default => 0.0,
        });
        $previous = $state->intervalDays;
        $interval = match ($rating) {
            ReviewRating::Hard => max(1, (int) round($previous * 1.2)),
            ReviewRating::Easy => $state->repetitions === 0 ? 4 : (int) round(max($previous + 1, $previous * $ease) * 1.3),
            default => match ($state->repetitions) {
                0 => 1,
                1 => 3,
                default => max($previous + 1, (int) round($previous * $ease)),
            },
        };
        $interval = min(self::MAX_INTERVAL_DAYS, $interval);

        return new ReviewState(
            $interval >= self::KNOWN_AFTER_DAYS ? WordStatus::Known : WordStatus::Learning,
            $ease,
            $interval,
            $state->repetitions + 1,
            $state->lapses,
            $now->addDays($interval),
        );
    }

    private static function ease(float $ease): float
    {
        return round(max(self::MIN_EASE, $ease), 2);
    }
}
