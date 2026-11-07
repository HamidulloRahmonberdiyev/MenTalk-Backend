<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Account\Models\LearnerProfile;
use App\Modules\Account\Models\User;
use App\Modules\Ai\Contracts\AiClient;
use App\Modules\Translator\Models\Translation;
use App\Modules\Vocabulary\Models\Word;
use App\Shared\Http\Middleware\DailyQuota;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Fakes\FakeAiClient;
use Tests\TestCase;

final class TranslatorTest extends TestCase
{
    use RefreshDatabase;

    private const WORD_ANSWER = [
        'sourceLanguage' => 'en',
        'targetLanguage' => 'uz',
        'translation' => 'olma',
        'reading' => '',
        'stressed' => 'ignored: not Russian',
        'correctedInput' => '',
        'meanings' => [
            ['translation' => 'olma', 'partOfSpeech' => 'ot', 'exampleSource' => 'I ate an apple.', 'exampleTarget' => 'Men olma yedim.'],
            ['translation' => '', 'partOfSpeech' => 'ot'],
        ],
        'alternatives' => [['text' => 'dropped: a word has meanings', 'register' => 'neutral']],
        'note' => '',
    ];

    private FakeAiClient $ai;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function fakeAi(array $answer): void
    {
        $this->app->instance(AiClient::class, $this->ai = new FakeAiClient($answer));
    }

    private function user(): User
    {
        $user = User::factory()->create();
        LearnerProfile::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    private function body(array $overrides = []): array
    {
        return $overrides + ['text' => 'apple', 'source' => 'auto', 'target' => 'uz', 'ui_language' => 'uz', 'uz_script' => 'latin'];
    }

    public function test_the_ai_is_asked_once_then_every_learner_gets_the_stored_answer(): void
    {
        $this->fakeAi(self::WORD_ANSWER);

        $this->actingAs($this->user())->postJson('/api/v1/translations', $this->body())
            ->assertOk()
            ->assertJsonPath('meta.stored', false)
            ->assertJsonPath('data.translation', 'olma')
            ->assertJsonPath('data.source_language', 'en')
            ->assertJsonPath('data.stressed', '')
            ->assertJsonCount(1, 'data.meanings')
            ->assertJsonPath('data.meanings.0.example.target', 'Men olma yedim.')
            ->assertJsonPath('data.alternatives', []);

        // Another learner, extra spaces: same row, no AI call.
        $this->actingAs($this->user())->postJson('/api/v1/translations', $this->body(['text' => '  apple ']))
            ->assertOk()
            ->assertJsonPath('meta.stored', true)
            ->assertJsonPath('data.translation', 'olma');

        $this->assertCount(1, $this->ai->requests);
        $this->assertSame(1, Translation::query()->sole()->hits);
    }

    public function test_the_answer_depends_on_direction_and_interface_language(): void
    {
        $this->fakeAi(self::WORD_ANSWER);
        $user = $this->user();

        $this->actingAs($user)->postJson('/api/v1/translations', $this->body())->assertOk();
        $this->actingAs($user)->postJson('/api/v1/translations', $this->body(['ui_language' => 'ru']))->assertJsonPath('meta.stored', false);
        $this->actingAs($user)->postJson('/api/v1/translations', $this->body(['target' => 'ru']))->assertJsonPath('meta.stored', false);
        $this->actingAs($user)->postJson('/api/v1/translations', $this->body(['uz_script' => 'cyrillic']))->assertJsonPath('meta.stored', false);

        $this->assertCount(4, $this->ai->requests);
    }

    public function test_the_uzbek_script_does_not_split_pairs_without_uzbek(): void
    {
        $this->fakeAi(['sourceLanguage' => 'de', 'targetLanguage' => 'en'] + self::WORD_ANSWER);
        $user = $this->user();
        $body = $this->body(['text' => 'Apfel', 'source' => 'de', 'target' => 'en']);

        $this->actingAs($user)->postJson('/api/v1/translations', $body)->assertOk();
        $this->actingAs($user)->postJson('/api/v1/translations', ['uz_script' => 'cyrillic'] + $body)->assertJsonPath('meta.stored', true);
    }

    public function test_a_single_word_enters_the_global_dictionary(): void
    {
        $this->fakeAi(self::WORD_ANSWER);

        $this->actingAs($this->user())->postJson('/api/v1/translations', $this->body())->assertOk();

        $word = Word::query()->where(['language' => 'en', 'normalized' => 'apple'])->sole();
        $this->assertSame(['olma'], $word->translations->pluck('text')->all());
    }

    public function test_a_sentence_is_stored_but_stays_out_of_the_dictionary(): void
    {
        $this->fakeAi(['translation' => 'Men olma yedim.', 'meanings' => [], 'alternatives' => [['text' => 'Olma yedim.', 'register' => 'bogus']]] + self::WORD_ANSWER);

        $this->actingAs($this->user())->postJson('/api/v1/translations', $this->body(['text' => 'I ate an apple.']))
            ->assertOk()
            ->assertJsonPath('data.alternatives.0.register', 'neutral');

        $this->assertSame(1, Translation::query()->count());
        $this->assertSame(0, Word::query()->count());
    }

    public function test_an_empty_ai_answer_is_neither_stored_nor_served(): void
    {
        $this->fakeAi(['translation' => '  '] + self::WORD_ANSWER);

        $this->actingAs($this->user())->postJson('/api/v1/translations', $this->body())->assertStatus(502);

        $this->assertSame(0, Translation::query()->count());
    }

    public function test_a_rewrite_is_not_passed_off_as_a_spelling_fix(): void
    {
        $this->fakeAi(['correctedInput' => 'I would like a green apple, please'] + self::WORD_ANSWER);

        $this->actingAs($this->user())->postJson('/api/v1/translations', $this->body(['text' => 'aple']))
            ->assertJsonPath('data.corrected_input', '');
    }

    public function test_only_ai_calls_count_against_the_daily_quota(): void
    {
        $this->fakeAi(self::WORD_ANSWER);
        $user = $this->user();

        $this->actingAs($user)->postJson('/api/v1/translations', $this->body())->assertOk();
        Cache::put(DailyQuota::key('lookups', $user->id), config('quota.lookups'));

        // Stored: still served with the allowance used up.
        $this->actingAs($user)->postJson('/api/v1/translations', $this->body())->assertOk();
        // New text needs the AI: refused.
        $this->actingAs($user)->postJson('/api/v1/translations', $this->body(['text' => 'pear']))
            ->assertStatus(429)->assertJsonPath('type', 'quota');
    }

    public function test_unknown_languages_are_rejected(): void
    {
        $this->fakeAi(self::WORD_ANSWER);
        $user = $this->user();

        $this->actingAs($user)->postJson('/api/v1/translations', $this->body(['target' => 'auto']))->assertUnprocessable();
        $this->actingAs($user)->postJson('/api/v1/translations', $this->body(['source' => 'xx']))->assertUnprocessable();
        $this->actingAs($user)->postJson('/api/v1/translations', $this->body(['ui_language' => 'de']))->assertUnprocessable();
    }

    public function test_unused_answers_are_pruned_and_reused_ones_kept(): void
    {
        $this->fakeAi(self::WORD_ANSWER);
        $user = $this->user();

        $this->actingAs($user)->postJson('/api/v1/translations', $this->body())->assertOk();
        $this->actingAs($user)->postJson('/api/v1/translations', $this->body())->assertOk();
        $this->actingAs($user)->postJson('/api/v1/translations', $this->body(['text' => 'appl']))->assertOk();

        $this->travel(Translation::UNUSED_RETENTION_DAYS + 1)->days();
        $this->artisan('model:prune', ['--model' => [Translation::class]])->assertSuccessful();

        $this->assertSame(['apple'], Translation::query()->pluck('text')->all());
    }
}
