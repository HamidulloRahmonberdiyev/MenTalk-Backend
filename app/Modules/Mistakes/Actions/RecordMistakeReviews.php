<?php

declare(strict_types=1);

namespace App\Modules\Mistakes\Actions;

use App\Modules\Account\Models\User;
use App\Modules\Mistakes\DTO\MistakeAnswer;
use App\Modules\Mistakes\Models\Mistake;
use App\Modules\Mistakes\Models\MistakeReview;
use App\Modules\Progress\Services\ProgressTracker;
use App\Modules\Progress\Support\Xp;
use App\Modules\Vocabulary\Support\Srs;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Logs a finished drill round, moves each mistake along its schedule and awards XP. Safe to resend. */
final readonly class RecordMistakeReviews
{
    public const MAX_ANSWERS = 30;

    public function __construct(private ProgressTracker $progress) {}

    /**
     * @param  Collection<int, MistakeAnswer>  $answers
     * @return int the XP the round earned
     */
    public function __invoke(User $user, Collection $answers): int
    {
        $answers = $answers->take(self::MAX_ANSWERS)->sortBy(fn (MistakeAnswer $answer) => $answer->answeredAt)->values();

        return DB::transaction(function () use ($user, $answers) {
            $mistakes = Mistake::query()
                ->where('user_id', $user->id)
                ->whereKey($answers->pluck('id')->unique()->values()->all())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $recalled = [];

            foreach ($answers as $answer) {
                $mistake = $mistakes->get($answer->id);

                // Not in this learner's notebook any more (or never was): nothing to schedule.
                if ($mistake === null || $mistake->dismissed_at !== null) {
                    continue;
                }

                // A resent round is already logged: it must not move the schedule or pay XP twice.
                $logged = MistakeReview::query()->insertOrIgnore([
                    'user_id' => $user->id,
                    'mistake_id' => $mistake->id,
                    'rating' => $answer->rating->value,
                    'exercise' => $answer->exercise,
                    'reviewed_at' => $answer->answeredAt->utc()->format('Y-m-d H:i:s.v'),
                    'duration_ms' => $answer->durationMs,
                ]) === 1;

                if (! $logged) {
                    continue;
                }

                $mistake->applyReviewState(Srs::review($mistake->reviewState(), $answer->rating, $answer->answeredAt));
                $mistake->last_reviewed_at = $answer->answeredAt;
                $mistake->save();
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
