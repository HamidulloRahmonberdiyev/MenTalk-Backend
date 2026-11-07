<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Account\Models\LearnerProfile;
use App\Modules\Account\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ActivityCalendarTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $profile = []): User
    {
        $user = User::factory()->create();
        LearnerProfile::factory()->create(['user_id' => $user->id, 'timezone' => 'Asia/Tashkent', 'daily_goal_minutes' => 10, ...$profile]);

        return $user;
    }

    private function day(User $user, string $day, int $minutes, int $xp = 0): void
    {
        DB::table('daily_progress')->insert(['user_id' => $user->id, 'day' => $day, 'minutes' => $minutes, 'xp' => $xp]);
    }

    private function talk(User $user, string $startedAtUtc, int $turns = 3): void
    {
        DB::table('conversations')->insert([
            'id' => (string) Str::ulid(), 'user_id' => $user->id, 'scenario_id' => 'cafe', 'state' => 'finished',
            'learner_turns' => $turns, 'duration_sec' => 300, 'started_at' => $startedAtUtc,
        ]);
    }

    public function test_month_lists_each_practised_day_in_the_learners_time_zone(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = $this->user();
        $this->day($user, '2026-10-03', 12, 20);
        $this->day($user, '2026-10-04', 4);
        $this->day($user, '2026-10-05', 0, 15);
        $this->day($user, '2026-09-30', 30);
        // 20:30 UTC on the 5th is already the 6th in Tashkent.
        $this->talk($user, '2026-10-05 20:30:00');
        $this->talk($user, '2026-10-03 09:00:00');
        $this->talk($user, '2026-10-03 10:00:00', turns: 0);

        $data = $this->actingAs($user)->getJson('/api/v1/progress/calendar?month=2026-10')->assertOk()->json('data');

        $this->assertSame('2026-10', $data['month']);
        $this->assertSame('2026-09-30', $data['since']);
        $this->assertSame(10, $data['goal_minutes']);
        $this->assertSame([
            ['date' => '2026-10-03', 'minutes' => 12, 'xp' => 20, 'conversations' => 1, 'goal_met' => true],
            ['date' => '2026-10-04', 'minutes' => 4, 'xp' => 0, 'conversations' => 0, 'goal_met' => false],
            ['date' => '2026-10-05', 'minutes' => 0, 'xp' => 15, 'conversations' => 0, 'goal_met' => false],
            ['date' => '2026-10-06', 'minutes' => 0, 'xp' => 0, 'conversations' => 1, 'goal_met' => false],
        ], $data['days']);
        $this->assertSame(['active_days' => 4, 'minutes' => 16, 'xp' => 35, 'conversations' => 2], $data['totals']);
        $this->assertSame(3, $data['best_streak']);
    }

    public function test_current_streak_counts_only_while_alive(): void
    {
        $today = CarbonImmutable::now('Asia/Tashkent');
        $alive = $this->user(['streak' => 5, 'last_active_on' => $today->subDay()->toDateString()]);
        $lapsed = $this->user(['streak' => 5, 'last_active_on' => $today->subDays(3)->toDateString()]);

        $this->actingAs($alive)->getJson('/api/v1/progress/calendar')->assertOk()
            ->assertJsonPath('data.streak', 5)->assertJsonPath('data.best_streak', 5)
            ->assertJsonPath('data.month', $today->format('Y-m'))->assertJsonPath('data.days', []);
        $this->actingAs($lapsed)->getJson('/api/v1/progress/calendar')->assertOk()->assertJsonPath('data.streak', 0);
    }

    public function test_month_must_be_well_formed(): void
    {
        $this->getJson('/api/v1/progress/calendar')->assertUnauthorized();
        $this->actingAs($this->user())->getJson('/api/v1/progress/calendar?month=2026-13')->assertUnprocessable();
    }
}
