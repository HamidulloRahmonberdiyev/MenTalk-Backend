<?php

declare(strict_types=1);

namespace App\Actions\Vocabulary;

use App\Models\User;
use App\Models\VocabCard;
use App\Services\Progress\ProgressTracker;
use App\Support\CardState;
use App\Support\Srs;
use App\Support\Xp;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class ReviewCards
{
    private const MAX_ANSWERS = 30;

    public function __construct(private ProgressTracker $progress) {}

    /** @param list<array{word: string, language: string, correct: bool, answered_at: string}> $answers */
    public function __invoke(User $user, array $answers): int
    {
        $answers = collect($answers)->take(self::MAX_ANSWERS)->sortBy('answered_at')->values();
        $cards = $user->cards()->whereIn('word', $answers->pluck('word'))->get()->keyBy(fn (VocabCard $card) => $card->identity());
        $xp = Xp::forRound($answers->pluck('correct')->map(fn ($c) => (bool) $c)->all());

        DB::transaction(function () use ($answers, $cards, $user, $xp) {
            foreach ($answers as $answer) {
                $card = $cards->get(VocabCard::identityOf($answer['language'], $answer['word']));

                if ($card === null) {
                    continue;
                }

                $next = Srs::review(
                    new CardState($card->stage, $card->due_at->toImmutable(), $card->streak, $card->lapses),
                    (bool) $answer['correct'],
                    CarbonImmutable::parse($answer['answered_at']),
                );

                $card->forceFill([
                    'stage' => $next->stage,
                    'due_at' => $next->dueAt,
                    'streak' => $next->streak,
                    'lapses' => $next->lapses,
                    'updated_at' => now(),
                    'synced_at' => now(),
                ])->save();
            }

            $this->progress->record($user, minutes: 0, xp: $xp);
        });

        return $xp;
    }
}
