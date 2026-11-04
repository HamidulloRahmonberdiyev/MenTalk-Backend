<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ConversationState;
use App\Models\Certificate;
use App\Models\Conversation;
use App\Models\LearnerProfile;
use App\Models\Scenario;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CertificateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function learner(array $profile = []): User
    {
        $user = User::factory()->create(['name' => 'Ali Valiyev']);
        LearnerProfile::factory()->create([
            'user_id' => $user->id,
            'target_language' => 'ru',
            'level' => 'intermediate',
            'level_source' => 'placement',
            'placement_score' => 75,
            ...$profile,
        ]);

        return $user;
    }

    private function rate(User $user, int $times, array $conversation = [], array $scores = ['speech' => 8, 'vocabulary' => 6, 'grammar' => 7]): void
    {
        foreach (range(1, $times) as $index) {
            Conversation::query()->create([
                'user_id' => $user->id,
                'scenario_id' => Scenario::query()->value('id'),
                'target_language' => 'ru',
                'state' => ConversationState::Finished,
                'learner_turns' => 5,
                'started_at' => now()->subMinutes($index),
                'ended_at' => now()->subMinutes($index),
                ...$conversation,
            ])->result()->create([...$scores, 'overall' => 7, 'mistakes' => [], 'new_words' => []]);
        }
    }

    public function test_progress_is_shown_until_the_requirements_are_met(): void
    {
        $user = $this->learner();
        $this->rate($user, 4);

        $this->actingAs($user)->getJson('/api/v1/certificate')->assertOk()
            ->assertJsonPath('data.placed', true)
            ->assertJsonPath('data.conversations', 4)
            ->assertJsonPath('data.required_conversations', 5)
            ->assertJsonPath('data.eligible', false)
            ->assertJsonPath('data.certificate', null);

        $this->actingAs($user)->postJson('/api/v1/certificate')->assertUnprocessable()->assertJsonPath('type', 'certificate_not_earned');
    }

    public function test_a_level_picked_by_hand_earns_no_certificate(): void
    {
        $user = $this->learner(['level_source' => 'self', 'placement_score' => null]);
        $this->rate($user, 6);

        $this->actingAs($user)->getJson('/api/v1/certificate')->assertJsonPath('data.placed', false)->assertJsonPath('data.eligible', false);
        $this->actingAs($user)->postJson('/api/v1/certificate')->assertUnprocessable();
    }

    public function test_short_chats_and_other_languages_do_not_count(): void
    {
        $user = $this->learner();
        $this->rate($user, 3);
        $this->rate($user, 3, ['learner_turns' => 1]);
        $this->rate($user, 3, ['target_language' => 'en']);

        $this->actingAs($user)->getJson('/api/v1/certificate')->assertJsonPath('data.conversations', 3);
    }

    public function test_a_certificate_records_the_scores_and_is_issued_once_per_level(): void
    {
        $user = $this->learner();
        $this->rate($user, 5);

        $first = $this->actingAs($user)->postJson('/api/v1/certificate')->assertCreated()
            ->assertJsonPath('data.cefr', 'B1')
            ->assertJsonPath('data.holder_name', 'Ali Valiyev')
            ->assertJsonPath('data.speaking', 8)
            ->assertJsonPath('data.vocabulary', 6)
            ->assertJsonPath('data.grammar', 7)
            ->assertJsonPath('data.overall', 7);

        $user->update(['name' => 'Someone Else']);
        $again = $this->actingAs($user)->postJson('/api/v1/certificate')->assertOk();

        $this->assertSame($first->json('data.serial'), $again->json('data.serial'));
        $this->assertSame('Ali Valiyev', $again->json('data.holder_name'));
        $this->assertSame(1, Certificate::query()->count());
    }

    public function test_reaching_the_next_level_earns_a_new_certificate(): void
    {
        $user = $this->learner();
        $this->rate($user, 5);
        $this->actingAs($user)->postJson('/api/v1/certificate')->assertCreated();

        $user->profile->update(['level' => 'advanced']);

        $this->actingAs($user)->postJson('/api/v1/certificate')->assertCreated()->assertJsonPath('data.cefr', 'B2+');
        $this->assertSame(2, Certificate::query()->count());
    }

    public function test_the_document_belongs_to_its_holder(): void
    {
        $user = $this->learner();
        $this->rate($user, 5);
        $serial = $this->actingAs($user)->postJson('/api/v1/certificate')->json('data.serial');

        $html = $this->actingAs($user)->getJson("/api/v1/certificate/{$serial}/document")->assertOk()->json('data.html');

        $this->assertStringContainsString('Ali Valiyev', $html);
        $this->assertStringContainsString($serial, $html);
        $this->assertStringContainsString('not the result of an official', $html);
        $this->assertStringContainsString('<svg', $html);

        $this->actingAs($this->learner())->getJson("/api/v1/certificate/{$serial}/document")->assertNotFound();
    }

    public function test_a_name_cannot_inject_markup_into_the_document(): void
    {
        $user = $this->learner();
        $user->update(['name' => '<script>alert(1)</script>']);
        $this->rate($user, 5);
        $serial = $this->actingAs($user)->postJson('/api/v1/certificate')->json('data.serial');

        $html = $this->actingAs($user)->getJson("/api/v1/certificate/{$serial}/document")->json('data.html');

        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_anyone_can_verify_a_certificate_until_the_account_is_gone(): void
    {
        $user = $this->learner();
        $this->rate($user, 5);
        $serial = $this->actingAs($user)->postJson('/api/v1/certificate')->json('data.serial');

        $this->app['auth']->forgetGuards();

        $this->get("/verify/{$serial}")->assertOk()->assertSee('Valid certificate')->assertSee('Ali Valiyev');
        $this->get('/verify/MT-2026-NOPE-NOPE')->assertNotFound();

        $user->delete();

        $this->get("/verify/{$serial}")->assertNotFound();
    }

    public function test_the_certificate_needs_a_signed_in_learner(): void
    {
        $this->getJson('/api/v1/certificate')->assertUnauthorized();
        $this->postJson('/api/v1/certificate')->assertUnauthorized();
    }
}
