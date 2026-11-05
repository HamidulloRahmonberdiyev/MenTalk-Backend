<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Actions;

use App\Modules\Account\Models\User;
use App\Modules\Progress\Services\ProgressTracker;
use App\Modules\Progress\Support\Xp;
use App\Modules\Vocabulary\DTO\ReviewAnswer;
use App\Modules\Vocabulary\Models\ReviewLog;
use App\Modules\Vocabulary\Services\UserWordLookup;
use App\Modules\Vocabulary\Support\Srs;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Logs a finished review round, advances the schedules and awards XP. Safe to resend. */
final readonly class RecordReviews
{
    private const MAX_ANSWERS = 30;

    public function __construct(private ProgressTracker $progress, private UserWordLookup $lookup) {}

    /**
     * @param  Collection<int, ReviewAnswer>  $answers
     * @return int the XP the round earned
     */
    public function __invoke(User $user, Collection $answers): int
    {
        $now = CarbonImmutable::now();
        $answers = $answers->take(self::MAX_ANSWERS)->sortBy(fn (ReviewAnswer $answer) => $answer->answeredAt)->values();
        $find = $this->lookup->load($user, $answers);

        return DB::transaction(function () use ($user, $answers, $find, $now) {
            $recalled = [];

            foreach ($answers as $answer) {
                $userWord = $find($answer->id, $answer->language, $answer->word);

                if ($userWord === null || $userWord->trashed()) {
                    continue;
                }

                // A resent round is already logged: it must not move the schedule or pay XP twice.
                $logged = ReviewLog::query()->insertOrIgnore([
                    'user_id' => $user->id,
                    'word_id' => $userWord->word_id,
                    'rating' => $answer->rating->value,
                    'reviewed_at' => $answer->answeredAt->utc()->format('Y-m-d H:i:s.v'),
                    'duration_ms' => $answer->durationMs,
                ]) === 1;

                if (! $logged) {
                    continue;
                }

                $userWord->applyReviewState(Srs::review($userWord->reviewState(), $answer->rating, $answer->answeredAt));
                $userWord->forceFill(['last_reviewed_at' => $answer->answeredAt, 'synced_at' => $now])->save();
                $recalled[] = $answer->rating->recalled();
            }

            if ($recalled === []) {
                return 0;
            }

            $xp = Xp::forRound($recalled);
            $this->progress->record($user, minutes: 0, xp: $xp);

            return $xp;
        });
    }
}
