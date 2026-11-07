<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Account\Models\LearnerProfile;
use App\Modules\Account\Models\User;
use App\Modules\Ai\Contracts\AiClient;
use App\Modules\Ai\DTO\AiRequest;
use App\Modules\Ai\Exceptions\AiUnavailable;
use App\Modules\Pronunciation\Models\Phrase;
use App\Modules\Pronunciation\Support\StarterPhrases;
use App\Shared\Http\Middleware\DailyQuota;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Tests\Fakes\FakeAiClient;
use Tests\TestCase;

final class PronunciationPhrasesTest extends TestCase
{
    use RefreshDatabase;

    private const NEW_LINES = ['Where is the nearest bakery?', 'We were very worried.', 'Thanks for the warm welcome.', 'Wave to the van driver.'];

    private FakeAiClient $ai;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->fakeAi(self::NEW_LINES);
    }

    private function fakeAi(array $lines): void
    {
        $this->app->instance(AiClient::class, $this->ai = new FakeAiClient(['phrases' => $lines]));
    }

    private function user(): User
    {
        $user = User::factory()->create();
        LearnerProfile::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    private function next(User $user, array $body = []): TestResponse
    {
        return $this->actingAs($user)->postJson('/api/v1/pronunciation/phrases/next', $body + ['language' => 'en', 'level' => 'easy', 'limit' => 5]);
    }

    /** @return list<string> */
    private function texts(TestResponse $response): array
    {
        return $response->assertOk()->json('data.*.text');
    }

    public function test_the_starter_pool_is_served_without_the_ai_and_without_repeats(): void
    {
        $user = $this->user();
        $starter = StarterPhrases::LINES['en']['easy'];

        $first = $this->texts($this->next($user));
        $second = $this->texts($this->next($user, ['limit' => 3]));

        $this->assertCount(5, $first);
        $this->assertCount(3, $second);
        $this->assertSame([], array_intersect($first, $second));
        $this->assertEqualsCanonicalizing($starter, [...$first, ...$second]);
        $this->assertSame([], $this->ai->requests);
    }

    public function test_one_ai_batch_is_written_when_the_pool_runs_out_and_shared_with_everyone(): void
    {
        $user = $this->user();
        $this->next($user, ['limit' => 8]); // the whole starter pool

        $this->assertEqualsCanonicalizing(self::NEW_LINES, $this->texts($this->next($user, ['limit' => 4])));
        $this->assertCount(1, $this->ai->requests);
        // The model is shown what exists, so it writes something else.
        $this->assertStringContainsString('Three thin trees.', $this->ai->requests[0]->system);

        // A second learner reads the new lines too, without another AI call.
        $other = $this->user();
        $this->next($other, ['limit' => 10])->assertJsonCount(10, 'data');
        $this->next($other, ['limit' => 2])->assertJsonCount(2, 'data');
        $this->assertCount(1, $this->ai->requests);
    }

    public function test_unusable_and_duplicate_ai_lines_are_dropped(): void
    {
        $this->fakeAi([
            'Good morning, Walter.',
            'good morning,   walter',    // same line, other case and punctuation
            'THREE THIN TREES!',         // a starter line
            'I have 3 cats.',            // digits
            str_repeat('very ', 10).'long', // too long for easy
            '',
        ]);
        $user = $this->user();
        $this->next($user, ['limit' => 8]);

        $this->assertSame(['Good morning, Walter.'], $this->texts($this->next($user, ['limit' => 1])));
        $this->assertSame(1, Phrase::query()->where('source', 'ai')->count());
    }

    public function test_without_quota_the_lines_seen_longest_ago_come_back_first(): void
    {
        $user = $this->user();
        $first = $this->texts($this->next($user, ['limit' => 3]));
        $this->travel(1)->minutes();
        $this->next($user, ['limit' => 5]);
        $this->travel(1)->minutes();
        Cache::put(DailyQuota::key('phrases', $user->id), config('quota.phrases'));

        $this->assertEqualsCanonicalizing($first, $this->texts($this->next($user, ['limit' => 3])));
        $this->assertSame([], $this->ai->requests);
    }

    public function test_an_ai_outage_falls_back_to_repeats_instead_of_failing(): void
    {
        $this->app->instance(AiClient::class, new class implements AiClient
        {
            public function json(AiRequest $request): array
            {
                throw new AiUnavailable;
            }

            public function text(AiRequest $request): string
            {
                throw new AiUnavailable;
            }

            public function streamJson(AiRequest $request, callable $onText): array
            {
                throw new AiUnavailable;
            }
        });
        $user = $this->user();
        $this->next($user, ['limit' => 8]);

        $this->next($user, ['limit' => 2])->assertJsonCount(2, 'data');
    }

    public function test_each_language_and_level_has_its_own_pool(): void
    {
        $lines = $this->texts($this->next($this->user(), ['language' => 'ru', 'level' => 'hard', 'limit' => 6]));

        $this->assertEqualsCanonicalizing(StarterPhrases::LINES['ru']['hard'], $lines);
    }

    public function test_bad_input_is_rejected(): void
    {
        $user = $this->user();

        $this->next($user, ['level' => 'extreme'])->assertUnprocessable();
        $this->next($user, ['language' => 'xx'])->assertUnprocessable();
        $this->next($user, ['limit' => 50])->assertUnprocessable();
    }
}
