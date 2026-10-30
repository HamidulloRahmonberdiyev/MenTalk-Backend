<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\AiClient;
use App\Contracts\SpeechSynthesizer;
use App\DTO\SpeechClip;
use App\Models\Conversation;
use App\Models\Language;
use App\Models\LearnerProfile;
use App\Models\Scenario;
use App\Models\User;
use App\Models\VocabCard;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeAiClient;
use Tests\Fakes\FakeSpeech;
use Tests\TestCase;

/** The app teaches several languages; everything a learner makes belongs to one of them. */
final class LanguageTest extends TestCase
{
    use RefreshDatabase;

    private FakeAiClient $ai;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->app->instance(AiClient::class, $this->ai = new FakeAiClient(
            ['transcript' => '', 'reply' => 'Hello!', 'emotion' => 'happy', 'mistakes' => [], 'form' => 'coffee', 'translation' => 'kofe'],
            'ok',
        ));
        $this->app->instance(SpeechSynthesizer::class, new FakeSpeech);
    }

    private function user(array $profile = []): User
    {
        $user = User::factory()->create();
        LearnerProfile::factory()->create(['user_id' => $user->id] + $profile);

        return $user;
    }

    private function system(int $request = 0): string
    {
        return $this->ai->requests[$request]->system;
    }

    public function test_a_conversation_in_english_is_held_by_the_english_tutor(): void
    {
        $this->actingAs($this->user())
            ->postJson('/api/v1/conversations', ['scenario_id' => 'cafe', 'target_language' => 'en'])
            ->assertCreated()->assertJsonPath('data.target_language', 'en');

        $this->assertStringContainsString('You are Emma', $this->system());
        $this->assertStringContainsString('A café in London', $this->system());
        $this->assertStringContainsString('Always reply in English only', $this->system());
        $this->assertStringNotContainsString('{', $this->system());
        $this->assertSame('en', Conversation::query()->sole()->target_language);
    }

    public function test_older_clients_get_the_language_from_the_profile(): void
    {
        $this->actingAs($this->user(['target_language' => 'en']))
            ->postJson('/api/v1/conversations', ['scenario_id' => 'cafe'])
            ->assertCreated()->assertJsonPath('data.target_language', 'en');

        $this->actingAs($this->user())
            ->postJson('/api/v1/conversations', ['scenario_id' => 'cafe'])
            ->assertCreated()->assertJsonPath('data.target_language', 'ru');

        $this->assertStringContainsString('You are Anna', $this->system(1));
        $this->assertStringContainsString('Russian (Cyrillic)', $this->system(1));
    }

    public function test_the_conversation_keeps_its_language_when_the_learner_switches(): void
    {
        $user = $this->user();
        $id = $this->actingAs($user)->postJson('/api/v1/conversations', ['scenario_id' => 'cafe'])->json('data.conversation_id');

        $this->actingAs($user)->putJson('/api/v1/me', ['target_language' => 'en'])->assertOk()->assertJsonPath('data.target_language', 'en');
        $this->actingAs($user)->postJson("/api/v1/conversations/{$id}/turns", ['text' => 'Привет'])->assertOk();

        $this->assertStringContainsString('You are Anna', $this->system(1));
    }

    public function test_unknown_or_switched_off_languages_are_rejected(): void
    {
        $user = $this->user();

        $this->actingAs($user)->postJson('/api/v1/conversations', ['scenario_id' => 'cafe', 'target_language' => 'xx'])
            ->assertUnprocessable()->assertJsonValidationErrors('target_language');
        $this->actingAs($user)->putJson('/api/v1/me', ['target_language' => 'xx'])
            ->assertUnprocessable()->assertJsonValidationErrors('target_language');

        Language::query()->whereKey('en')->update(['active' => false]);
        cache()->forget('languages');

        $this->actingAs($user)->postJson('/api/v1/conversations', ['scenario_id' => 'cafe', 'target_language' => 'en'])
            ->assertUnprocessable();
    }

    public function test_a_learner_whose_language_was_switched_off_gets_a_problem_response(): void
    {
        Language::query()->whereKey('en')->update(['active' => false]);
        cache()->forget('languages');

        $this->actingAs($this->user(['target_language' => 'en']))
            ->postJson('/api/v1/conversations', ['scenario_id' => 'cafe'])
            ->assertStatus(422)->assertJsonPath('type', 'unsupported_language');
    }

    public function test_a_scenario_without_a_brief_in_the_language_cannot_be_started(): void
    {
        Scenario::factory()->playableIn('ru')->create(['id' => 'bank']);

        $this->actingAs($this->user())
            ->postJson('/api/v1/conversations', ['scenario_id' => 'bank', 'target_language' => 'en'])
            ->assertNotFound()->assertJsonPath('type', 'scenario_unavailable');
        $this->actingAs($this->user())
            ->postJson('/api/v1/conversations', ['scenario_id' => 'missing'])
            ->assertNotFound()->assertJsonPath('type', 'scenario_unavailable');
    }

    public function test_the_voice_of_the_language_is_used_when_set(): void
    {
        Language::query()->whereKey('en')->update(['voice' => 'Kore']);
        cache()->forget('languages');
        $speech = new class implements SpeechSynthesizer
        {
            public array $voices = [];

            public function speak(string $text, string $voice): SpeechClip
            {
                $this->voices[] = $voice;

                return new SpeechClip('https://cdn.test/a.wav', 1200);
            }
        };
        $this->app->instance(SpeechSynthesizer::class, $speech);

        $this->actingAs($this->user())->postJson('/api/v1/conversations', ['scenario_id' => 'cafe', 'target_language' => 'en'])->assertCreated();

        $this->assertSame(['Kore'], $speech->voices);
    }

    public function test_hint_and_evaluation_are_written_for_the_conversation_language(): void
    {
        $user = $this->user(['ui_language' => 'ru']);
        $id = $this->actingAs($user)->postJson('/api/v1/conversations', ['scenario_id' => 'cafe', 'target_language' => 'en'])
            ->json('data.conversation_id');
        $this->actingAs($user)->postJson("/api/v1/conversations/{$id}/turns", ['text' => 'A coffee, please'])->assertOk();

        $this->actingAs($user)->postJson("/api/v1/conversations/{$id}/hint")->assertOk();
        $this->assertStringContainsString('English sentence', $this->system(2));

        $this->actingAs($user)->postJson("/api/v1/conversations/{$id}/finish")->assertOk();
        $this->assertStringContainsString("learner's English", $this->system(3));
        $this->assertStringContainsString('translation as Russian', $this->system(3));
    }

    public function test_the_same_word_is_a_separate_card_in_each_language(): void
    {
        $user = $this->user();
        $card = fn (array $o) => $o + [
            'word' => 'tort', 'translation' => 'tort', 'stage' => 1,
            'due_at' => '2026-10-10T10:00:00Z', 'updated_at' => '2026-10-10T10:00:00Z',
        ];

        $this->actingAs($user)->postJson('/api/v1/vocabulary/sync', ['changes' => [
            $card(['language' => 'en', 'translation' => 'cake?']),
            $card(['language' => 'ru']),
            $card([]),
        ]])->assertOk()->assertJsonCount(2, 'data.cards');

        $this->assertSame(['en', 'ru'], VocabCard::query()->orderBy('language')->pluck('language')->all());

        $this->actingAs($user)->postJson('/api/v1/vocabulary/reviews', ['answers' => [
            ['word' => 'tort', 'language' => 'en', 'correct' => true, 'answered_at' => now()->toIso8601String()],
        ]])->assertOk();

        $this->assertSame(['en' => 2, 'ru' => 1], VocabCard::query()->pluck('stage', 'language')->all());
    }

    public function test_cards_of_a_switched_off_language_still_sync(): void
    {
        Language::query()->whereKey('en')->update(['active' => false]);
        cache()->forget('languages');

        $this->actingAs($this->user())->postJson('/api/v1/vocabulary/sync', ['changes' => [[
            'word' => 'coffee', 'language' => 'en', 'translation' => 'kofe', 'stage' => 1,
            'due_at' => '2026-10-10T10:00:00Z', 'updated_at' => '2026-10-10T10:00:00Z',
        ]]])->assertOk()->assertJsonPath('data.cards.0.language', 'en');
    }

    public function test_lookup_explains_the_word_in_its_own_language_and_caches_per_language(): void
    {
        $user = $this->user();
        $lookup = fn (array $o) => $this->actingAs($user)->postJson('/api/v1/vocabulary/lookup', $o + ['word' => 'tort', 'sentence' => 'tort', 'language' => 'uz'])->assertOk();

        $lookup(['target_language' => 'en']);
        $lookup(['target_language' => 'en']);
        $lookup([]);

        $this->assertCount(2, $this->ai->requests);
        $this->assertStringContainsString('Given a word in English', $this->system(0));
        $this->assertStringContainsString('Given a word in Russian', $this->system(1));
    }

    public function test_translate_accepts_taught_languages(): void
    {
        $this->actingAs($this->user())->postJson('/api/v1/translate', ['text' => 'Salom', 'language' => 'en'])->assertOk();
        $this->actingAs($this->user())->postJson('/api/v1/translate', ['text' => 'Salom', 'language' => 'xx'])->assertUnprocessable();

        $this->assertStringContainsString('into English', $this->system());
    }
}
