<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\AiClient;
use App\Contracts\IdentityVerifier;
use App\Contracts\SpeechSynthesizer;
use App\DTO\Identity;
use App\Jobs\PurgeUserData;
use App\Models\Conversation;
use App\Models\LearnerProfile;
use App\Models\Scenario;
use App\Models\User;
use App\Models\VocabCard;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Fakes\FakeAiClient;
use Tests\Fakes\FakeSpeech;
use Tests\TestCase;

final class ApiTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        $user = User::factory()->create();
        LearnerProfile::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    private function fakeAi(array $json = [], string $text = 'ok'): FakeAiClient
    {
        $this->seed(DatabaseSeeder::class);
        $this->app->instance(AiClient::class, $ai = new FakeAiClient($json, $text));
        $this->app->instance(SpeechSynthesizer::class, new FakeSpeech);

        return $ai;
    }

    public function test_bootstrap_lists_scenarios_and_supports_etag(): void
    {
        $this->seed(DatabaseSeeder::class);

        $first = $this->getJson('/api/v1/bootstrap')->assertOk()->assertJsonPath('scenarios.0.id', 'cafe');
        $this->getJson('/api/v1/bootstrap', ['If-None-Match' => $first->headers->get('ETag')])->assertStatus(304);
    }

    public function test_google_login_issues_a_token(): void
    {
        $this->app->instance(IdentityVerifier::class, new class implements IdentityVerifier
        {
            public function verify(string $idToken): Identity
            {
                return new Identity('g-1', 'a@b.test', 'Ali');
            }
        });

        $token = $this->postJson('/api/v1/auth/google', ['id_token' => 'x', 'device' => 'iPhone'])
            ->assertOk()->assertJsonPath('data.user.email', 'a@b.test')->json('data.token');

        $this->withToken($token)->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.name', 'Ali');
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_conversation_flow_stores_turns_and_finishes_idempotently(): void
    {
        $ai = $this->fakeAi(['transcript' => '', 'reply' => 'Привет!', 'emotion' => 'happy', 'mistakes' => []]);
        $user = $this->user();

        $id = $this->actingAs($user)->postJson('/api/v1/conversations', ['scenario_id' => 'cafe'])
            ->assertCreated()->assertJsonPath('data.reply', 'Привет!')->assertJsonPath('data.audio_url', 'https://cdn.test/a.wav')
            ->json('data.conversation_id');

        $this->actingAs($user)->postJson("/api/v1/conversations/{$id}/turns", ['text' => 'Привет'])
            ->assertOk()->assertJsonPath('data.transcript', 'Привет');

        $this->assertSame(3, Conversation::query()->findOrFail($id)->turns()->count());

        $this->app->instance(AiClient::class, new FakeAiClient(['speech' => 8, 'vocabulary' => 7, 'grammar' => 6, 'mistakes' => [], 'newWords' => []]));

        $this->actingAs($user)->postJson("/api/v1/conversations/{$id}/finish")->assertOk()->assertJsonPath('data.overall', 7);
        $this->actingAs($user)->postJson("/api/v1/conversations/{$id}/finish")->assertOk()->assertJsonPath('data.overall', 7);

        $this->actingAs($user)->getJson('/api/v1/stats')->assertOk()->assertJsonPath('data.streak', 1);
        $this->actingAs($user)->postJson("/api/v1/conversations/{$id}/turns", ['text' => 'Ещё'])->assertForbidden();
        $this->assertCount(2, $ai->requests);
    }

    public function test_cannot_write_into_someone_elses_conversation(): void
    {
        $this->fakeAi();
        $conversation = Conversation::factory()->create();

        $this->actingAs($this->user())
            ->postJson("/api/v1/conversations/{$conversation->id}/turns", ['text' => 'Привет'])
            ->assertForbidden();
    }

    public function test_daily_quota_returns_problem_json(): void
    {
        config(['quota.turns' => 0]);
        $this->fakeAi();
        Scenario::factory()->create(['id' => 'x']);

        $this->actingAs($this->user())->postJson('/api/v1/conversations', ['scenario_id' => 'x'])
            ->assertStatus(429)->assertJsonPath('type', 'quota');
    }

    public function test_vocabulary_sync_is_last_writer_wins_and_returns_tombstones(): void
    {
        $user = $this->user();
        $card = fn (array $o = []) => $o + [
            'word' => 'кофе', 'translation' => 'kofe', 'stage' => 1,
            'due_at' => '2026-10-10T10:00:00Z', 'updated_at' => '2026-10-10T10:00:00Z',
        ];

        $this->actingAs($user)->postJson('/api/v1/vocabulary/sync', ['cursor' => null, 'changes' => [$card()]])
            ->assertOk()->assertJsonCount(1, 'data.cards');

        $this->actingAs($user)->postJson('/api/v1/vocabulary/sync', ['changes' => [$card(['translation' => 'old', 'updated_at' => '2026-10-09T10:00:00Z'])]]);
        $this->assertSame('kofe', VocabCard::query()->firstOrFail()->translation);

        $this->actingAs($user)->postJson('/api/v1/vocabulary/sync', ['changes' => [$card(['deleted' => true, 'updated_at' => '2026-10-11T10:00:00Z'])]])
            ->assertJsonPath('data.cards.0.deleted', true);
    }

    public function test_review_updates_schedule_and_awards_xp(): void
    {
        $user = $this->user();
        VocabCard::query()->create([
            'user_id' => $user->id, 'word' => 'кофе', 'translation' => 'kofe', 'stage' => 0,
            'due_at' => now()->subDay(), 'updated_at' => now(), 'synced_at' => now(),
        ]);

        $this->actingAs($user)->postJson('/api/v1/vocabulary/reviews', [
            'answers' => [['word' => 'кофе', 'correct' => true, 'answered_at' => now()->toIso8601String()]],
        ])->assertOk()->assertJsonPath('data.xp', 30);

        $this->assertSame(1, VocabCard::query()->firstOrFail()->stage);
    }

    public function test_lookup_and_translate_use_the_ai_client(): void
    {
        $this->fakeAi(['form' => 'кофе', 'translation' => 'kofe'], 'salom');
        $user = $this->user();

        $this->actingAs($user)->postJson('/api/v1/vocabulary/lookup', ['word' => 'кофе', 'sentence' => 'Я хочу кофе', 'language' => 'uz'])
            ->assertOk()->assertJsonPath('data.translation', 'kofe');
        $this->actingAs($user)->postJson('/api/v1/translate', ['text' => 'Привет', 'language' => 'uz'])
            ->assertOk()->assertJsonPath('data.translation', 'salom');
    }

    public function test_account_deletion_soft_deletes_and_revokes_tokens(): void
    {
        Queue::fake();
        $user = $this->user();

        $this->actingAs($user)->deleteJson('/api/v1/me')->assertNoContent();

        $this->assertSoftDeleted($user);
        Queue::assertPushed(PurgeUserData::class);
    }
}
