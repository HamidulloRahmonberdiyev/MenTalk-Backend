<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Account\Models\LearnerProfile;
use App\Modules\Account\Models\User;
use App\Modules\Ai\Contracts\AiClient;
use App\Modules\Conversation\Models\Conversation;
use App\Modules\Mistakes\Actions\RecordMistakes;
use App\Modules\Mistakes\Models\Mistake;
use App\Modules\Speech\Contracts\SpeechSynthesizer;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\Fakes\FakeAiClient;
use Tests\Fakes\FakeSpeech;
use Tests\TestCase;

final class MistakesTest extends TestCase
{
    use RefreshDatabase;

    private const CASE = ['said' => 'Я иду в магазин вчера', 'correct' => 'Я ходил в магазин вчера', 'note' => "O'tgan zamon kerak", 'category' => 'verb_form'];

    private const ENDING = ['said' => 'без молоко', 'correct' => 'без молока', 'note' => 'Genitiv', 'category' => 'case_ending'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->app->instance(SpeechSynthesizer::class, new FakeSpeech);
    }

    private function user(string $ui = 'uz'): User
    {
        $user = User::factory()->create();
        LearnerProfile::factory()->create(['user_id' => $user->id, 'ui_language' => $ui]);

        return $user;
    }

    private function record(User $user, array $mistakes, ?CarbonImmutable $at = null, string $note = 'uz'): int
    {
        return app(RecordMistakes::class)($user->id, 'ru', $note, null, $mistakes, $at);
    }

    private function answer(User $user, string $id, bool $correct, CarbonImmutable $at): TestResponse
    {
        return $this->actingAs($user)->postJson('/api/v1/mistakes/reviews', ['answers' => [
            ['id' => $id, 'correct' => $correct, 'exercise' => 'choice', 'answered_at' => $at->toIso8601String(), 'duration_ms' => 3200],
        ]]);
    }

    public function test_corrections_from_a_conversation_land_in_the_notebook(): void
    {
        $this->app->instance(AiClient::class, new FakeAiClient([
            'transcript' => '',
            'reply' => 'Понятно!',
            'emotion' => 'happy',
            'mistakes' => [self::CASE, ['said' => 'привет', 'correct' => 'Привет!', 'note' => '', 'category' => 'spelling'], ['said' => 'кофе', 'correct' => 'кофе', 'note' => 'x', 'category' => 'other']],
        ]));
        $user = $this->user('ru');

        // The opening turn is the tutor's alone: nothing the learner said, nothing to collect.
        $id = $this->actingAs($user)->postJson('/api/v1/conversations', ['scenario_id' => 'cafe'])->assertCreated()->json('data.conversation_id');
        $this->assertSame(0, Mistake::query()->count());

        $this->actingAs($user)->postJson("/api/v1/conversations/{$id}/turns", ['text' => 'Я иду в магазин вчера'])->assertOk();

        // Only case or punctuation changed, or nothing at all: not worth practising.
        $mistake = Mistake::query()->sole();
        $this->assertSame([$user->id, 'ru', $id, 'ru', 'verb_form', 1], [$mistake->user_id, $mistake->language, $mistake->conversation_id, $mistake->note_language, $mistake->category->value, $mistake->occurrences]);

        $this->actingAs($user)->postJson("/api/v1/conversations/{$id}/turns", ['text' => 'Я иду в магазин вчера'])->assertOk();

        $this->assertSame(2, Mistake::query()->sole()->occurrences);
    }

    public function test_the_same_mistake_is_one_entry_whatever_the_case_and_punctuation(): void
    {
        $user = $this->user();

        $this->record($user, [self::ENDING, ['said' => 'Без молоко!', 'correct' => 'без  молока.', 'note' => '', 'category' => 'nonsense']]);
        $this->record($user, [['said' => 'без молоко', 'correct' => 'Без молока', 'note' => 'Yangi izoh', 'category' => 'whatever']]);

        $mistake = Mistake::query()->sole();
        $this->assertSame(2, $mistake->occurrences);
        // A newer note wins; an unknown label does not overwrite a known one.
        $this->assertSame(['Yangi izoh', 'case_ending'], [$mistake->note, $mistake->category->value]);
    }

    public function test_a_learner_reads_only_their_own_notebook_in_the_language_asked(): void
    {
        $user = $this->user();
        $this->record($user, [self::CASE, self::ENDING]);
        app(RecordMistakes::class)($user->id, 'en', 'uz', null, [['said' => 'He go', 'correct' => 'He goes', 'note' => '', 'category' => 'agreement']]);
        $this->record($this->user(), [['said' => 'я есть', 'correct' => 'я ем', 'note' => '', 'category' => 'verb_form']]);

        $this->actingAs($user)->getJson('/api/v1/mistakes?language=ru')->assertOk()->assertJsonCount(2, 'data');
        $this->actingAs($user)->getJson('/api/v1/mistakes?language=ru&category=case_ending')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.correct', 'без молока');

        $this->actingAs($user)->getJson('/api/v1/mistakes/summary?language=ru')->assertOk()
            ->assertJsonPath('data.total', 2)->assertJsonPath('data.due', 2)->assertJsonPath('data.mastered', 0)
            ->assertJsonPath('data.categories.verb_form', 1)->assertJsonPath('data.categories.case_ending', 1);

        $this->actingAs($this->user())->getJson('/api/v1/mistakes/summary?language=ru')->assertOk()
            ->assertJsonPath('data.total', 0)->assertJsonPath('data.categories', []);
        $this->actingAs($user)->getJson('/api/v1/mistakes')->assertUnprocessable();
    }

    public function test_drilling_moves_the_schedule_pays_xp_once_and_survives_a_resend(): void
    {
        $user = $this->user();
        $this->record($user, [self::CASE]);
        $id = Mistake::query()->sole()->id;
        $now = CarbonImmutable::now();

        $this->actingAs($user)->getJson('/api/v1/mistakes/practice?language=ru')->assertOk()->assertJsonPath('data.0.id', $id);

        $xp = $this->answer($user, $id, true, $now)->assertOk()->json('data.xp');
        $this->assertGreaterThan(0, $xp);
        $this->assertSame(0, $this->answer($user, $id, true, $now)->assertOk()->json('data.xp'));

        $mistake = Mistake::query()->sole();
        $this->assertSame(['learning', 1, 1], [$mistake->status->value, $mistake->repetitions, $mistake->interval_days]);
        $this->assertSame(1, DB::table('mistake_reviews')->count());
        $this->actingAs($user)->getJson('/api/v1/mistakes/summary?language=ru')->assertJsonPath('data.due', 0);

        // Nothing is due, yet the round is topped up with what is not mastered: there is always practice.
        $this->actingAs($user)->getJson('/api/v1/mistakes/practice?language=ru')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_due_mistakes_come_first_and_mastered_ones_only_when_due(): void
    {
        $user = $this->user();
        $this->record($user, [self::CASE, self::ENDING, ['said' => 'в Ташкенте живу я', 'correct' => 'я живу в Ташкенте', 'note' => '', 'category' => 'word_order']]);
        Mistake::query()->where('category', 'verb_form')->update(['status' => 'known', 'interval_days' => 30, 'due_at' => now()->addDays(30)]);
        Mistake::query()->where('category', 'case_ending')->update(['status' => 'learning', 'due_at' => now()->addDay(), 'lapses' => 3]);

        $ids = $this->actingAs($user)->getJson('/api/v1/mistakes/practice?language=ru&limit=5')->assertOk()->json('data.*.category');

        $this->assertSame(['word_order', 'case_ending'], $ids);
    }

    public function test_making_a_mastered_mistake_again_brings_it_back(): void
    {
        $user = $this->user();
        $this->record($user, [self::ENDING]);
        Mistake::query()->update(['status' => 'known', 'interval_days' => 30, 'repetitions' => 5, 'due_at' => now()->addDays(30)]);

        $this->record($user, [self::ENDING]);

        $mistake = Mistake::query()->sole();
        $this->assertSame(['learning', 0, 1], [$mistake->status->value, $mistake->repetitions, $mistake->lapses]);
        $this->assertFalse($mistake->due_at->isFuture());
    }

    public function test_not_a_mistake_is_set_aside_for_good_and_can_be_undone(): void
    {
        $user = $this->user();
        $this->record($user, [self::ENDING]);
        $id = Mistake::query()->sole()->id;

        $this->actingAs($this->user())->deleteJson("/api/v1/mistakes/{$id}")->assertNotFound();
        $this->actingAs($user)->deleteJson("/api/v1/mistakes/{$id}")->assertNoContent();

        $this->record($user, [self::ENDING]);
        $this->actingAs($user)->getJson('/api/v1/mistakes?language=ru')->assertOk()->assertJsonCount(0, 'data');
        $this->assertSame(0, $this->answer($user, $id, true, CarbonImmutable::now())->assertOk()->json('data.xp'));

        $this->actingAs($user)->postJson("/api/v1/mistakes/{$id}/restore")->assertOk()->assertJsonPath('data.occurrences', 2);
        $this->actingAs($user)->getJson('/api/v1/mistakes?language=ru')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_someone_elses_mistake_cannot_be_drilled(): void
    {
        $owner = $this->user();
        $this->record($owner, [self::ENDING]);
        $id = Mistake::query()->sole()->id;

        $this->assertSame(0, $this->answer($this->user(), $id, true, CarbonImmutable::now())->assertOk()->json('data.xp'));
        $this->assertSame(0, DB::table('mistake_reviews')->count());
    }

    public function test_bad_answers_are_rejected(): void
    {
        $user = $this->user();

        $this->actingAs($user)->postJson('/api/v1/mistakes/reviews', ['answers' => []])->assertUnprocessable();
        $this->actingAs($user)->postJson('/api/v1/mistakes/reviews', ['answers' => [['id' => 'nope', 'correct' => true, 'answered_at' => now()->toIso8601String()]]])
            ->assertUnprocessable()->assertJsonValidationErrors('answers.0.id');
        $this->actingAs($user)->postJson('/api/v1/mistakes/reviews', ['answers' => [['id' => (string) str()->ulid(), 'correct' => true, 'answered_at' => now()->addDay()->toIso8601String()]]])
            ->assertUnprocessable()->assertJsonValidationErrors('answers.0.answered_at');
    }

    public function test_corrections_already_in_past_conversations_fill_the_notebook_once(): void
    {
        $user = $this->user();
        $conversation = Conversation::factory()->create(['user_id' => $user->id, 'target_language' => 'ru', 'started_at' => now()->subWeek()]);
        foreach ([[self::ENDING], [self::ENDING, ['said' => 'старый', 'correct' => 'старая', 'note' => 'Rod']]] as $i => $mistakes) {
            $conversation->turns()->create(['role' => 'learner', 'text' => '…', 'mistakes' => $mistakes, 'created_at' => now()->subDays(7 - $i)]);
        }

        Schema::dropIfExists('mistake_reviews');
        Schema::dropIfExists('user_mistakes');
        (require database_path('migrations/2026_11_07_000003_create_mistakes_notebook.php'))->up();

        $this->assertSame(2, Mistake::query()->count());
        $this->assertSame(2, Mistake::query()->where('correct', 'без молока')->sole()->occurrences);
        $this->assertSame('other', Mistake::query()->where('correct', 'старая')->sole()->category->value);
    }
}
