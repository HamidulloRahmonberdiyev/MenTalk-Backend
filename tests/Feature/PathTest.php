<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\LearnerProfile;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PathTest extends TestCase
{
    use RefreshDatabase;

    private function learner(string $language = 'en'): User
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        LearnerProfile::factory()->create(['user_id' => $user->id, 'target_language' => $language]);

        return $user;
    }

    private function finished(User $user, string $scenario, int $score, string $language = 'en'): void
    {
        Conversation::factory()->create([
            'user_id' => $user->id,
            'scenario_id' => $scenario,
            'target_language' => $language,
            'state' => 'finished',
            'score' => $score,
            'ended_at' => now(),
        ]);
    }

    public function test_a_new_learner_starts_on_the_first_stage_with_the_rest_locked(): void
    {
        $user = $this->learner();

        $this->actingAs($user)->getJson('/api/v1/path')->assertOk()
            ->assertJsonPath('data.language', 'en')
            ->assertJsonPath('data.passed', 0)
            ->assertJsonPath('data.stages.0.scenario_id', 'intro')
            ->assertJsonPath('data.stages.0.stage', 1)
            ->assertJsonPath('data.stages.0.status', 'open')
            ->assertJsonPath('data.stages.1.status', 'locked');
    }

    public function test_passing_a_stage_opens_the_next_and_keeps_the_best_score(): void
    {
        $user = $this->learner();
        $this->finished($user, 'intro', 4);
        $this->finished($user, 'intro', 9);
        $this->finished($user, 'cafe', 5);

        $this->actingAs($user)->getJson('/api/v1/path')->assertOk()
            ->assertJsonPath('data.passed', 1)
            ->assertJsonPath('data.stages.0.status', 'passed')
            ->assertJsonPath('data.stages.0.best_score', 9)
            ->assertJsonPath('data.stages.0.attempts', 2)
            ->assertJsonPath('data.stages.0.stars', 3)
            ->assertJsonPath('data.stages.1.status', 'open')
            ->assertJsonPath('data.stages.1.best_score', 5)
            ->assertJsonPath('data.stages.1.stars', 0)
            ->assertJsonPath('data.stages.2.status', 'locked');
    }

    public function test_progress_is_kept_per_language(): void
    {
        $user = $this->learner();
        $this->finished($user, 'intro', 8, 'ru');

        $this->actingAs($user)->getJson('/api/v1/path')->assertJsonPath('data.stages.0.status', 'open');
        $this->actingAs($user)->getJson('/api/v1/path?language=ru')
            ->assertJsonPath('data.language', 'ru')
            ->assertJsonPath('data.stages.0.status', 'passed')
            ->assertJsonPath('data.stages.0.stars', 2);
        $this->actingAs($user)->getJson('/api/v1/path?language=xx')->assertUnprocessable();
    }
}
