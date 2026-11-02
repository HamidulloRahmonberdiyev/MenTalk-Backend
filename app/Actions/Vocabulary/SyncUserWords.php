<?php

declare(strict_types=1);

namespace App\Actions\Vocabulary;

use App\DTO\SyncResult;
use App\DTO\UserWordChange;
use App\Models\User;
use App\Models\UserWord;
use App\Services\Vocabulary\Dictionary;
use App\Services\Vocabulary\UserWordLookup;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Two-way sync of the learner's word list. Devices push what changed locally (last writer wins on
 * `updated_at`) and pull every row the server changed after their cursor, deletions included.
 */
final readonly class SyncUserWords
{
    private const PAGE = 500;

    public function __construct(private AddWordToUser $add, private Dictionary $dictionary, private UserWordLookup $lookup) {}

    /** @param Collection<int, UserWordChange> $changes */
    public function __invoke(User $user, ?CarbonImmutable $cursor, Collection $changes): SyncResult
    {
        $now = CarbonImmutable::now();
        $find = $this->lookup->load($user, $changes);

        DB::transaction(function () use ($user, $changes, $find, $now) {
            foreach ($changes as $change) {
                $userWord = $find($change->id, $change->language, $change->word);

                if ($userWord === null && $change->deleted) {
                    continue;
                }

                if ($userWord !== null && $userWord->updated_at->gte($change->updatedAt)) {
                    continue;
                }

                $this->apply($userWord ?? ($this->add)($user, $change->toNewWord(), $change->source), $change, $now);
            }
        });

        $words = $user->userWords()->withTrashed()->with(['word', 'translation'])
            ->when($cursor, fn ($query) => $query->where('synced_at', '>', $cursor))
            ->where('synced_at', '<=', $now)
            ->orderBy('synced_at')->limit(self::PAGE)->get();

        return new SyncResult($words, $words->count() === self::PAGE ? $words->last()->synced_at : $now);
    }

    private function apply(UserWord $userWord, UserWordChange $change, CarbonImmutable $now): void
    {
        if ($change->translation !== null && $change->translation !== $userWord->translation?->text) {
            $userWord->translation_word_id = $this->dictionary->translate($userWord->word, $change->translationLanguage, $change->translation)->id;
        }

        $userWord->applyReviewState($change->state);
        $userWord->forceFill([
            'form' => $change->form,
            'context_sentence' => $change->contextSentence,
            'last_reviewed_at' => $change->lastReviewedAt,
            'updated_at' => $change->updatedAt,
            'synced_at' => $now,
            'deleted_at' => $change->deleted ? $now : null,
        ])->save();
    }
}
