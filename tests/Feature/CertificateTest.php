<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Account\Models\LearnerProfile;
use App\Modules\Account\Models\User;
use App\Modules\Certificate\Models\Certificate;
use App\Modules\Conversation\Enums\ConversationState;
use App\Modules\Conversation\Models\Conversation;
use App\Modules\Conversation\Models\Scenario;
use App\Modules\Vocabulary\Models\UserWord;
use App\Modules\Vocabulary\Services\Dictionary;
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

    /** A learner who has done nothing: the level was picked by hand, no test, no chat, no word. */
    private function idle(): User
    {
        return $this->learner(['level' => 'advanced', 'level_source' => 'self', 'placement_score' => null]);
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

    private function save(User $user, int $count, string $language = 'ru'): void
    {
        $dictionary = app(Dictionary::class);

        foreach (range(1, $count) as $index) {
            UserWord::query()->create([
                'user_id' => $user->id,
                'word_id' => $dictionary->entry($language, "слово{$index}")->id,
                'source' => 'manual',
            ]);
        }
    }

    public function test_a_learner_who_did_nothing_gets_no_certificate(): void
    {
        $user = $this->idle();

        $this->actingAs($user)->getJson('/api/v1/certificate')->assertOk()
            ->assertJsonPath('data.eligible', false)
            ->assertJsonPath('data.level', null)
            ->assertJsonPath('data.placed', false)
            ->assertJsonPath('data.words', 0)
            ->assertJsonPath('data.certificate', null);

        $this->actingAs($user)->postJson('/api/v1/certificate')->assertUnprocessable()->assertJsonPath('type', 'certificate_not_earned');
    }

    public function test_a_single_saved_word_earns_a_certificate_at_the_level_it_shows(): void
    {
        $user = $this->idle();
        $this->save($user, 1);

        // The hand-picked "advanced" proves nothing: one word is a beginner's certificate.
        $this->actingAs($user)->getJson('/api/v1/certificate')->assertJsonPath('data.eligible', true)->assertJsonPath('data.cefr', 'A1');

        $serial = $this->actingAs($user)->postJson('/api/v1/certificate')->assertCreated()
            ->assertJsonPath('data.cefr', 'A1')
            ->assertJsonPath('data.speaking', null)
            ->assertJsonPath('data.grammar', null)
            ->assertJsonPath('data.words', 1)
            ->assertJsonPath('data.placement_score', null)
            ->json('data.serial');

        $html = $this->actingAs($user)->getJson("/api/v1/certificate/{$serial}/document")->json('data.html');
        $this->assertStringContainsString('1 saved word', $html);
        $this->assertStringContainsString('not assessed', $html);
    }

    public function test_words_alone_never_claim_more_than_elementary(): void
    {
        $user = $this->idle();
        $this->save($user, 600, 'ru');

        $this->actingAs($user)->postJson('/api/v1/certificate')->assertCreated()->assertJsonPath('data.cefr', 'A2');
    }

    public function test_the_placement_test_alone_is_enough(): void
    {
        $user = $this->learner();

        $this->actingAs($user)->postJson('/api/v1/certificate')->assertCreated()
            ->assertJsonPath('data.cefr', 'B1')
            ->assertJsonPath('data.overall', 7.5)
            ->assertJsonPath('data.vocabulary', null);
    }

    public function test_short_chats_and_other_languages_do_not_count(): void
    {
        $user = $this->learner();
        $this->rate($user, 3);
        $this->rate($user, 3, ['learner_turns' => 1]);
        $this->rate($user, 3, ['target_language' => 'en']);
        $this->save($user, 2, 'en');

        $this->actingAs($user)->getJson('/api/v1/certificate')->assertJsonPath('data.conversations', 3)->assertJsonPath('data.words', 0);
    }

    public function test_conversations_record_their_scores(): void
    {
        $user = $this->learner();
        $this->rate($user, 5);

        $this->actingAs($user)->postJson('/api/v1/certificate')->assertCreated()
            ->assertJsonPath('data.cefr', 'B1')
            ->assertJsonPath('data.holder_name', 'Ali Valiyev')
            ->assertJsonPath('data.speaking', 8)
            ->assertJsonPath('data.vocabulary', 6)
            ->assertJsonPath('data.grammar', 7)
            ->assertJsonPath('data.overall', 7)
            ->assertJsonPath('data.conversations', 5);
    }

    public function test_asking_again_without_progress_returns_the_same_certificate(): void
    {
        $user = $this->learner();
        $this->rate($user, 5);

        $first = $this->actingAs($user)->postJson('/api/v1/certificate')->assertCreated();

        $user->update(['name' => 'Someone Else']);
        $again = $this->actingAs($user)->postJson('/api/v1/certificate')->assertOk();

        $this->assertSame($first->json('data.serial'), $again->json('data.serial'));
        $this->assertSame('Ali Valiyev', $again->json('data.holder_name'));
        $this->assertSame(1, Certificate::query()->count());
    }

    public function test_progress_earns_a_new_certificate_and_the_old_one_stays_valid(): void
    {
        $user = $this->learner();
        $this->rate($user, 5);
        $old = $this->actingAs($user)->postJson('/api/v1/certificate')->assertCreated()->json('data.serial');

        $this->save($user, 3);
        $this->actingAs($user)->postJson('/api/v1/certificate')->assertCreated()->assertJsonPath('data.words', 3);

        $this->rate($user, 5, scores: ['speech' => 9, 'vocabulary' => 9, 'grammar' => 9]);
        $this->actingAs($user)->postJson('/api/v1/certificate')->assertCreated()->assertJsonPath('data.cefr', 'B2+');

        $this->assertSame(3, Certificate::query()->count());
        $this->actingAs($user)->getJson('/api/v1/certificate')->assertJsonPath('data.certificate.cefr', 'B2+');
        $this->app['auth']->forgetGuards();
        $this->get("/verify/{$old}")->assertOk()->assertSee('Valid certificate');
    }

    public function test_the_top_level_needs_enough_conversations(): void
    {
        $user = $this->learner(['level' => 'advanced', 'placement_score' => 95]);
        $this->rate($user, 2, scores: ['speech' => 10, 'vocabulary' => 10, 'grammar' => 10]);

        $this->actingAs($user)->postJson('/api/v1/certificate')->assertJsonPath('data.cefr', 'B1');
    }

    public function test_the_document_belongs_to_its_holder(): void
    {
        $user = $this->learner();
        $this->rate($user, 5);
        $serial = $this->actingAs($user)->postJson('/api/v1/certificate')->json('data.serial');

        $html = $this->actingAs($user)->getJson("/api/v1/certificate/{$serial}/document")->assertOk()->json('data.html');

        $this->assertStringContainsString('Ali Valiyev', $html);
        $this->assertStringContainsString($serial, $html);
        $this->assertStringContainsString('a placement test and 5 tutor-rated conversations', $html);
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

        $this->get("/verify/{$serial}")->assertOk()->assertSee('Valid certificate')->assertSee('Ali Valiyev')->assertSee('5 tutor-rated conversations');
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
