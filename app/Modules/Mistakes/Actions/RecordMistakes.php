<?php

declare(strict_types=1);

namespace App\Modules\Mistakes\Actions;

use App\Modules\Mistakes\DTO\Correction;
use App\Modules\Mistakes\Models\Mistake;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** Writes the tutor's corrections into the learner's notebook, merging the ones already there. */
final class RecordMistakes
{
    /** A turn rarely has more; anything beyond is noise the learner would not get through. */
    private const MAX_PER_TURN = 5;

    /**
     * @param  array<mixed>  $mistakes  as the model returned them
     * @return int how many were written or merged
     */
    public function __invoke(string $userId, string $language, string $noteLanguage, ?string $conversationId, array $mistakes, ?CarbonImmutable $at = null): int
    {
        $at ??= CarbonImmutable::now();
        $corrections = collect($mistakes)
            ->map(fn (mixed $raw) => Correction::from($raw))
            ->filter()
            // Twice in one turn is one mistake.
            ->unique(fn (Correction $correction) => $correction->fingerprint)
            ->take(self::MAX_PER_TURN);

        if ($corrections->isEmpty()) {
            return 0;
        }

        return DB::transaction(function () use ($corrections, $userId, $language, $noteLanguage, $conversationId, $at) {
            foreach ($corrections as $correction) {
                // Runs in a savepoint: two turns racing to add the same mistake end up on one row.
                $mistake = Mistake::query()->createOrFirst(
                    ['user_id' => $userId, 'language' => $language, 'fingerprint' => $correction->fingerprint],
                    [
                        'said' => $correction->said,
                        'correct' => $correction->correct,
                        'note' => $correction->note === '' ? null : $correction->note,
                        'note_language' => $correction->note === '' ? null : $noteLanguage,
                        'category' => $correction->category,
                        'conversation_id' => $conversationId,
                        'first_seen_at' => $at,
                        'last_seen_at' => $at,
                        'due_at' => $at,
                    ],
                );

                if (! $mistake->wasRecentlyCreated) {
                    $mistake->recur($at, $correction->note, $noteLanguage, $correction->category, $conversationId);
                }
            }

            return $corrections->count();
        });
    }
}
