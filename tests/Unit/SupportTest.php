<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\CardState;
use App\Support\Srs;
use App\Support\Streaks;
use App\Support\Text;
use App\Support\Xp;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

final class SupportTest extends TestCase
{
    public function test_due_card_moves_up_and_is_pushed_away(): void
    {
        $now = CarbonImmutable::parse('2026-10-10 12:00');
        $next = Srs::review(new CardState(2, $now, 1, 0), true, $now);

        $this->assertSame(3, $next->stage);
        $this->assertTrue($next->dueAt->equalTo($now->addDays(3)));
    }

    public function test_miss_drops_two_stages(): void
    {
        $now = CarbonImmutable::parse('2026-10-10 12:00');

        $this->assertSame(2, Srs::review(new CardState(4, $now, 3, 0), false, $now)->stage);
    }

    public function test_early_practice_does_not_inflate_schedule(): void
    {
        $now = CarbonImmutable::parse('2026-10-10 12:00');

        $this->assertSame(2, Srs::review(new CardState(2, $now->addDays(2), 0, 0), true, $now)->stage);
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
