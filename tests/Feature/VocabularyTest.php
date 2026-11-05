<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Account\Models\LearnerProfile;
use App\Modules\Account\Models\User;
use App\Modules\Language\Models\Language;
use App\Modules\Vocabulary\Actions\AddWordToUser;
use App\Modules\Vocabulary\DTO\NewWord;
use App\Modules\Vocabulary\Enums\ReviewRating;
use App\Modules\Vocabulary\Enums\WordSource;
use App\Modules\Vocabulary\Enums\WordStatus;
use App\Modules\Vocabulary\Models\ReviewLog;
use App\Modules\Vocabulary\Models\UserWord;
use App\Modules\Vocabulary\Models\Word;
use App\Modules\Vocabulary\Services\Dictionary;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

/** The global dictionary, the learner's word list on top of it, its sync and its reviews. */
final class VocabularyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function user(array $profile = []): User
    {
        $user = User::factory()->create();
        LearnerProfile::factory()->create(['user_id' => $user->id, 'ui_language' => 'uz'] + $profile);

        return $user;
    }

    private function add(User $user, string $text, string $language = 'ru', ?string $translation = 'kofe', ?string $sentence = null): UserWord
    {
        return app(AddWordToUser::class)($user, new NewWord($language, $text, $translation, 'uz', $sentence), WordSource::AiChat);
    }

    private function change(array $override = []): array
    {
        return $override + [
            'word' => 'кофе', 'language' => 'ru', 'translation' => 'kofe',
            'status' => 'learning', 'ease' => 2.5, 'interval_days' => 1, 'repetitions' => 1, 'lapses' => 0,
            'due_at' => '2026-10-10T10:00:00Z', 'updated_at' => '2026-10-10T10:00:00Z',
        ];
    }

    public function test_a_spelling_gets_one_dictionary_entry_however_it_is_written(): void
    {
        $dictionary = app(Dictionary::class);

        $first = $dictionary->entry('ru', 'Ёлка');
        $this->assertTrue($first->is($dictionary->entry('ru', ' ёлка ')));
        $this->assertTrue($first->is($dictionary->entry('ru', 'елка')));
        $this->assertSame('Ёлка', $first->text);

        $this->assertTrue($dictionary->entry('ar', 'كِتَاب')->is($dictionary->entry('ar', 'كتاب')));
        $this->assertSame(2, Word::query()->count());
    }

    public function test_the_same_spelling_is_a_separate_word_per_language_and_known_part_of_speech(): void
    {
        $dictionary = app(Dictionary::class);

        $ru = $dictionary->entry('ru', 'tort');
        $this->assertFalse($ru->is($dictionary->entry('en', 'tort')));

        // An unknown part of speech reuses the entry; a known, different one is a word of its own.
        $verb = $dictionary->entry('en', 'run', 'verb');
        $this->assertTrue($verb->is($dictionary->entry('en', 'run')));
        $this->assertFalse($verb->is($dictionary->entry('en', 'run', 'noun')));
    }

    public function test_adding_a_word_from_chat_links_it_to_the_learner_once(): void
    {
        $user = $this->user();
        $other = $this->user();

        $first = $this->add($user, 'кофе', sentence: 'Я хочу кофе');
        $again = $this->add($user, 'Кофе', sentence: 'Кофе горячий');
        $this->add($other, 'кофе');

        $this->assertTrue($first->is($again));
        $this->assertSame(1, $user->words()->count());
        $this->assertSame(1, Word::query()->where('language', 'ru')->count());
        $this->assertSame(2, UserWord::query()->count());
        $this->assertSame('Я хочу кофе', $again->context_sentence);
        $this->assertSame(WordSource::AiChat, $first->source);
        $this->assertSame(WordStatus::New, $first->status);
        $this->assertSame(['kofe'], $user->words()->first()->translations->pluck('text')->all());
    }

    public function test_a_deleted_word_comes_back_with_its_history(): void
    {
        $user = $this->user();
        $this->add($user, 'кофе')->forceFill(['repetitions' => 3])->save();
        UserWord::query()->firstOrFail()->delete();

        $back = $this->add($user, 'кофе', sentence: 'Ещё кофе');

        $this->assertFalse($back->trashed());
        $this->assertSame([3, 'Ещё кофе'], [$back->repetitions, $back->context_sentence]);
        $this->assertSame(1, UserWord::withTrashed()->count());
    }

    public function test_each_learner_keeps_the_meaning_they_saved(): void
    {
        $user = $this->user();
        $other = $this->user();

        $this->add($user, 'кофе', translation: 'kofe');
        $this->add($other, 'кофе', translation: 'qahva');

        $this->assertSame('kofe', $user->userWords()->firstOrFail()->translation->text);
        $this->assertSame('qahva', $other->userWords()->firstOrFail()->translation->text);
        $this->assertSame(['kofe', 'qahva'], Word::query()->where('normalized', 'кофе')->firstOrFail()->translations->pluck('text')->sort()->values()->all());
    }

    public function test_sync_is_last_writer_wins_and_returns_tombstones(): void
    {
        $user = $this->user();

        $id = $this->actingAs($user)->postJson('/api/v1/vocabulary/sync', ['cursor' => null, 'changes' => [$this->change(['context_sentence' => 'Я хочу кофе'])]])
            ->assertOk()->assertJsonCount(1, 'data.words')
            ->assertJsonPath('data.words.0.word', 'кофе')
            ->assertJsonPath('data.words.0.translation', 'kofe')
            ->assertJsonPath('data.words.0.translation_language', 'uz')
            ->assertJsonPath('data.words.0.status', 'learning')
            ->json('data.words.0.id');

        $this->actingAs($user)->postJson('/api/v1/vocabulary/sync', ['changes' => [$this->change(['translation' => 'old', 'updated_at' => '2026-10-09T10:00:00Z'])]]);
        $this->assertSame('kofe', UserWord::query()->firstOrFail()->translation->text);

        // Known by id from now on, so a respelling still finds the row.
        $this->actingAs($user)->postJson('/api/v1/vocabulary/sync', ['changes' => [$this->change(['id' => $id, 'word' => 'Кофе', 'deleted' => true, 'updated_at' => '2026-10-11T10:00:00Z'])]])
            ->assertJsonPath('data.words.0.id', $id)
            ->assertJsonPath('data.words.0.deleted', true);
        $this->assertSame(1, UserWord::withTrashed()->count());
    }

    public function test_sync_only_returns_what_changed_after_the_cursor(): void
    {
        $user = $this->user();
        $cursor = $this->actingAs($user)->postJson('/api/v1/vocabulary/sync', ['changes' => [$this->change()]])->json('data.cursor');

        $this->travel(1)->seconds();
        $this->actingAs($user)->postJson('/api/v1/vocabulary/sync', ['cursor' => $cursor, 'changes' => [$this->change(['word' => 'чай', 'translation' => 'choy'])]])
            ->assertJsonCount(1, 'data.words')->assertJsonPath('data.words.0.word', 'чай');
    }

    public function test_the_same_word_is_a_separate_entry_in_each_language(): void
    {
        $user = $this->user();

        $this->actingAs($user)->postJson('/api/v1/vocabulary/sync', ['changes' => [
            $this->change(['word' => 'tort', 'language' => 'en', 'translation' => 'tort']),
            $this->change(['word' => 'tort', 'language' => 'ru']),
            $this->change(['word' => 'tort']),
        ]])->assertOk()->assertJsonCount(2, 'data.words');

        $this->assertSame(['en', 'ru'], $user->words()->orderBy('language')->pluck('language')->all());
    }

    public function test_words_of_a_switched_off_language_still_sync(): void
    {
        Language::query()->whereKey('en')->update(['active' => false]);
        cache()->forget('languages');

        $this->actingAs($this->user())->postJson('/api/v1/vocabulary/sync', ['changes' => [$this->change(['word' => 'coffee', 'language' => 'en'])]])
            ->assertOk()->assertJsonPath('data.words.0.language', 'en');
    }

    public function test_review_advances_the_schedule_logs_it_and_awards_xp(): void
    {
        $user = $this->user();
        $this->add($user, 'кофе');

        $this->actingAs($user)->postJson('/api/v1/vocabulary/reviews', [
            'answers' => [['word' => 'кофе', 'language' => 'ru', 'rating' => ReviewRating::Good->value, 'answered_at' => now()->toIso8601String(), 'duration_ms' => 1800]],
        ])->assertOk()->assertJsonPath('data.xp', 30);

        $userWord = UserWord::query()->firstOrFail();
        $this->assertSame([WordStatus::Learning, 1, 1], [$userWord->status, $userWord->interval_days, $userWord->repetitions]);
        $this->assertNotNull($userWord->last_reviewed_at);
        $this->assertSame([ReviewRating::Good, 1800], [ReviewLog::query()->firstOrFail()->rating, ReviewLog::query()->firstOrFail()->duration_ms]);
        $this->assertSame(30, $user->profile->refresh()->xp);
    }

    public function test_a_resent_round_neither_moves_the_schedule_nor_pays_twice(): void
    {
        $this->travelTo('2026-10-10 09:00:00');
        $user = $this->user();
        $this->add($user, 'кофе');
        $round = ['answers' => [['word' => 'кофе', 'language' => 'ru', 'correct' => true, 'answered_at' => '2026-10-10T10:00:00.250Z']]];

        $this->actingAs($user)->postJson('/api/v1/vocabulary/reviews', $round)->assertJsonPath('data.xp', 30);
        $this->actingAs($user)->postJson('/api/v1/vocabulary/reviews', $round)->assertJsonPath('data.xp', 0);

        $this->assertSame(1, ReviewLog::query()->count());
        $this->assertSame(1, UserWord::query()->firstOrFail()->repetitions);
        $this->assertSame(30, $user->profile->refresh()->xp);
    }

    public function test_review_history_outlives_the_learner_and_cannot_be_edited(): void
    {
        $user = $this->user();
        $this->add($user, 'кофе');
        $this->actingAs($user)->postJson('/api/v1/vocabulary/reviews', [
            'answers' => [['word' => 'кофе', 'language' => 'ru', 'rating' => 1, 'answered_at' => now()->toIso8601String()]],
        ])->assertOk();

        $user->forceDelete();

        $log = ReviewLog::query()->firstOrFail();
        $this->assertNull($log->user_id);
        $this->assertNotNull($log->word_id);
        $this->assertSame(0, UserWord::query()->count());

        $this->expectException(LogicException::class);
        $log->update(['rating' => ReviewRating::Easy]);
    }
}
