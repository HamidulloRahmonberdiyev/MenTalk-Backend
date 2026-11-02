<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\ReviewRating;
use App\Enums\WordStatus;
use App\Support\ReviewState;
use App\Support\Srs;
use App\Support\Streaks;
use App\Support\Text;
use App\Support\Xp;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

final class SupportTest extends TestCase
{
    public function test_good_answers_follow_the_sm2_ladder(): void
    {
        $now = CarbonImmutable::parse('2026-10-10 12:00');
        $state = ReviewState::fresh($now);
        $intervals = [];

        foreach (range(1, 4) as $_) {
            $state = Srs::review($state, ReviewRating::Good, $state->dueAt);
            $intervals[] = $state->intervalDays;
        }

        $this->assertSame([1, 3, 8, 20], $intervals);
        $this->assertSame(WordStatus::Learning, $state->status);
        $this->assertSame(WordStatus::Known, Srs::review($state, ReviewRating::Good, $state->dueAt)->status);
    }

    public function test_hard_and_easy_move_the_ease(): void
    {
        $now = CarbonImmutable::parse('2026-10-10 12:00');
        $state = new ReviewState(WordStatus::Learning, 2.5, 10, 3, 0, $now);

        $hard = Srs::review($state, ReviewRating::Hard, $now);
        $easy = Srs::review($state, ReviewRating::Easy, $now);

        $this->assertSame([2.35, 12], [$hard->ease, $hard->intervalDays]);
        $this->assertSame([2.65, 34], [$easy->ease, $easy->intervalDays]);
    }

    public function test_a_lapse_starts_the_word_over_and_returns_it_soon(): void
    {
        $now = CarbonImmutable::parse('2026-10-10 12:00');
        $next = Srs::review(new ReviewState(WordStatus::Known, 1.4, 40, 6, 1, $now), ReviewRating::Again, $now);

        $this->assertSame([WordStatus::Learning, 1.3, 0, 0, 2], [$next->status, $next->ease, $next->intervalDays, $next->repetitions, $next->lapses]);
        $this->assertTrue($next->dueAt->equalTo($now->addMinutes(10)));
    }

    public function test_early_practice_does_not_inflate_schedule(): void
    {
        $now = CarbonImmutable::parse('2026-10-10 12:00');
        $state = new ReviewState(WordStatus::Learning, 2.5, 3, 2, 0, $now->addDays(2));

        $this->assertSame($state, Srs::review($state, ReviewRating::Easy, $now));
    }

    public function test_xp_rewards_combo_and_perfect_round(): void
    {
        $this->assertSame(0, Xp::forRound([]));
        $this->assertSame(10 + 12 + 14 + 20, Xp::forRound([true, true, true]));
        $this->assertSame(10, Xp::forRound([true, false]));
    }

    public function test_streak_rules(): void
    {
        $today = CarbonImmutable::parse('2026-10-10');

        $this->assertSame(1, Streaks::next(0, null, $today));
        $this->assertSame(5, Streaks::next(5, $today, $today));
        $this->assertSame(6, Streaks::next(5, $today->subDay(), $today));
        $this->assertSame(1, Streaks::next(5, $today->subDays(3), $today));
    }

    public function test_emoji_are_stripped_for_speech(): void
    {
        $this->assertSame('Отлично! Вам с молоком?', Text::withoutEmoji('Отлично! Вам с молоком? ☕'));
    }
}
