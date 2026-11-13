<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Account\Models\LearnerProfile;
use App\Modules\Account\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // A Wednesday, so this week started on 2026-11-09.
        $this->travelTo(CarbonImmutable::parse('2026-11-11 12:00:00'));
    }

    /** Learners are created in order, and their ULIDs sort the same way: the earlier one wins a tie. */
    private function learner(string $name, int $xp = 0, array $weekDays = []): User
    {
        $user = User::factory()->create(['name' => $name]);
        LearnerProfile::factory()->create(['user_id' => $user->id, 'xp' => $xp]);

        foreach ($weekDays as $day => $dayXp) {
            DB::table('daily_progress')->insert(['user_id' => $user->id, 'day' => $day, 'minutes' => 0, 'xp' => $dayXp]);
        }

        // ULIDs made in the same millisecond are not ordered, so space them out.
        usleep(2000);

        return $user;
    }

    public function test_all_time_ranks_by_xp_with_ties_to_the_earlier_learner_and_hides_surnames(): void
    {
        $this->learner('Aziza Karimova', 500);
        $me = $this->learner('Hamidullo Rahmonberdiyev', 300);
        $this->learner('Bekzod', 300);
        $this->learner('Nobody Yet', 0);

        $data = $this->actingAs($me)->getJson('/api/v1/leaderboard?period=all')->assertOk()->json('data');

        $this->assertSame('all', $data['period']);
        $this->assertNull($data['since']);
        $this->assertSame([
            ['rank' => 1, 'name' => 'Aziza K.', 'avatar_url' => null, 'xp' => 500, 'is_me' => false],
            ['rank' => 2, 'name' => 'Hamidullo R.', 'avatar_url' => null, 'xp' => 300, 'is_me' => true],
            ['rank' => 3, 'name' => 'Bekzod', 'avatar_url' => null, 'xp' => 300, 'is_me' => false],
        ], $data['top']);
        $this->assertSame(['rank' => 2, 'xp' => 300], $data['me']);
    }

    public function test_week_counts_only_xp_since_monday_and_is_the_default(): void
    {
        $old = $this->learner('Veteran', 9000, ['2026-11-08' => 900]);
        $fresh = $this->learner('Newcomer', 40, ['2026-11-09' => 15, '2026-11-11' => 25]);

        $data = $this->actingAs($old)->getJson('/api/v1/leaderboard')->assertOk()->json('data');

        $this->assertSame('week', $data['period']);
        $this->assertSame('2026-11-09', $data['since']);
        $this->assertSame([['rank' => 1, 'name' => 'Newcomer', 'avatar_url' => null, 'xp' => 40, 'is_me' => false]], $data['top']);
        // Nothing this week yet: no place on the board.
        $this->assertSame(['rank' => null, 'xp' => 0], $data['me']);

        $this->actingAs($fresh)->getJson('/api/v1/leaderboard?period=week')->assertOk()
            ->assertJsonPath('data.me', ['rank' => 1, 'xp' => 40]);
    }

    public function test_me_keeps_its_rank_beyond_the_limit(): void
    {
        $this->learner('A', 30);
        $this->learner('B', 20);
        $me = $this->learner('C', 10);

        $this->actingAs($me)->getJson('/api/v1/leaderboard?period=all&limit=2')->assertOk()
            ->assertJsonCount(2, 'data.top')
            ->assertJsonPath('data.me', ['rank' => 3, 'xp' => 10]);
    }

    public function test_blocked_and_deleted_learners_are_left_off(): void
    {
        $blocked = $this->learner('Blocked', 900);
        $blocked->forceFill(['blocked_at' => now()])->save();
        $this->learner('Deleted', 800)->delete();
        $me = $this->learner('Me', 100);

        $this->actingAs($me)->getJson('/api/v1/leaderboard?period=all')->assertOk()
            ->assertJsonCount(1, 'data.top')
            ->assertJsonPath('data.me', ['rank' => 1, 'xp' => 100]);
    }

    public function test_validates_the_query_and_needs_a_session(): void
    {
        $this->getJson('/api/v1/leaderboard')->assertUnauthorized();

        $me = $this->learner('Me', 1);
        $this->actingAs($me)->getJson('/api/v1/leaderboard?period=month&limit=500')
            ->assertUnprocessable()->assertJsonValidationErrors(['period', 'limit']);
    }
}
