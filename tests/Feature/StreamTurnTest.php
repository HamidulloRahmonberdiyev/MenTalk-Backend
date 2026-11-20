<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Account\Models\LearnerProfile;
use App\Modules\Account\Models\User;
use App\Modules\Ai\Contracts\AiClient;
use App\Modules\Ai\Exceptions\AiUnavailable;
use App\Modules\Conversation\Enums\TurnRole;
use App\Modules\Conversation\Models\Conversation;
use App\Modules\Speech\Contracts\SpeechSynthesizer;
use App\Modules\Speech\DTO\SpeechClip;
use App\Shared\Http\Middleware\DailyQuota;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Fakes\FakeAiClient;
use Tests\Fakes\FakeSpeech;
use Tests\TestCase;

final class StreamTurnTest extends TestCase
{
    use RefreshDatabase;

    private const LONG_REPLY = 'Отлично, вы уже почти готовы! Скажите, пожалуйста, что вы хотите заказать сегодня на обед?';

    private function conversation(array $json): array
    {
        $this->seed(DatabaseSeeder::class);
        $this->app->instance(AiClient::class, new FakeAiClient($json));
        $this->app->instance(SpeechSynthesizer::class, new FakeSpeech);
        $user = User::factory()->create();
        LearnerProfile::factory()->create(['user_id' => $user->id]);
        $id = $this->actingAs($user)->postJson('/api/v1/conversations', ['scenario_id' => 'cafe'])->assertCreated()->json('data.conversation_id');

        return [$user, Conversation::query()->findOrFail($id)];
    }

    /** @return list<array{event: string, data: mixed}> */
    private function events(string $body): array
    {
        preg_match_all('/event: (\w+)\ndata: (.*)\n\n/', $body, $matches, PREG_SET_ORDER);

        return array_map(static fn (array $m) => ['event' => $m[1], 'data' => json_decode($m[2], true)], $matches);
    }

    private function stream(User $user, Conversation $conversation, array $body): array
    {
        $response = $this->actingAs($user)->post("/api/v1/conversations/{$conversation->id}/turns/stream", $body, ['Accept' => 'text/event-stream']);
        $response->assertOk();
        $this->assertStringStartsWith('text/event-stream', $response->headers->get('Content-Type'));

        return $this->events($response->streamedContent());
    }

    public function test_reply_streams_before_it_is_voiced_in_two_pieces(): void
    {
        [$user, $conversation] = $this->conversation(['transcript' => '', 'emotion' => 'happy', 'reply' => self::LONG_REPLY, 'mistakes' => []]);

        $events = $this->stream($user, $conversation, ['text' => 'Привет', 'clips' => true]);
        $names = array_column($events, 'event');

        $this->assertSame('delta', $names[0]);
        $this->assertSame(self::LONG_REPLY, implode('', array_column(array_column(array_filter($events, fn ($e) => $e['event'] === 'delta'), 'data'), 'text')));
        // `saved` comes once the turn is stored, between the first piece of voice and the rest.
        $this->assertSame(['reply', 'audio', 'saved', 'audio', 'done'], array_values(array_filter($names, fn ($n) => $n !== 'delta')));

        $reply = $events[array_search('reply', $names, true)]['data'];
        $this->assertSame(['id' => null, 'reply' => self::LONG_REPLY, 'emotion' => 'happy'], $reply);

        $audio = array_values(array_filter($events, fn ($e) => $e['event'] === 'audio'));
        $this->assertSame('Отлично, вы уже почти готовы!', $audio[0]['data']['text']);
        $this->assertFalse($audio[0]['data']['last']);
        $this->assertTrue($audio[1]['data']['last']);
        $this->assertSame('https://cdn.test/a.wav', $audio[1]['data']['url']);

        $tutor = $conversation->turns()->where('role', TurnRole::Tutor)->latest('id')->firstOrFail();
        $this->assertSame(self::LONG_REPLY, $tutor->text);
        $this->assertSame($tutor->id, end($events)['data']['id']);
        // The conversation is free again for the next turn.
        $this->assertTrue(Cache::lock("conversation:{$conversation->id}", 1)->get());
    }

    public function test_apps_without_clips_get_one_clip_for_the_whole_reply(): void
    {
        [$user, $conversation] = $this->conversation(['transcript' => '', 'emotion' => 'happy', 'reply' => self::LONG_REPLY, 'mistakes' => []]);

        $audio = array_values(array_filter($this->stream($user, $conversation, ['text' => 'Привет']), fn ($e) => $e['event'] === 'audio'));

        $this->assertSame([['url' => 'https://cdn.test/a.wav', 'ms' => 1200]], array_column($audio, 'data'));
        $this->assertSame('https://cdn.test/a.wav', $conversation->turns()->latest('id')->firstOrFail()->audio_url);
    }

    public function test_a_failed_voice_piece_is_reported_so_the_app_can_read_it_itself(): void
    {
        [$user, $conversation] = $this->conversation(['transcript' => '', 'emotion' => 'happy', 'reply' => self::LONG_REPLY, 'mistakes' => []]);
        $this->app->instance(SpeechSynthesizer::class, new class implements SpeechSynthesizer
        {
            public function speak(string $text, string $voice): SpeechClip
            {
                throw new AiUnavailable;
            }
        });

        $events = $this->stream($user, $conversation, ['text' => 'Привет', 'clips' => true]);
        $audio = array_values(array_filter($events, fn ($e) => $e['event'] === 'audio'));

        $this->assertCount(2, $audio);
        $this->assertNull($audio[0]['data']['url']);
        $this->assertSame('done', end($events)['event']);
    }

    public function test_voice_turn_streams_the_transcript_first(): void
    {
        [$user, $conversation] = $this->conversation(['transcript' => 'Я хочу кофе', 'emotion' => 'happy', 'reply' => 'Хорошо!', 'mistakes' => []]);

        $events = $this->stream($user, $conversation, ['audio' => base64_encode('fake'), 'mime' => 'audio/mp4', 'clips' => true]);

        $this->assertSame(['event' => 'transcript', 'data' => ['text' => 'Я хочу кофе']], $events[0]);
        $this->assertSame('Я хочу кофе', $conversation->turns()->where('role', TurnRole::Learner)->latest('id')->firstOrFail()->text);
    }

    public function test_model_failure_becomes_an_error_event(): void
    {
        [$user, $conversation] = $this->conversation(['transcript' => '', 'emotion' => 'happy', 'reply' => '', 'mistakes' => []]);

        $events = $this->stream($user, $conversation, ['text' => 'Привет']);

        $this->assertSame([['event' => 'error', 'data' => ['status' => 502]]], array_values(array_filter($events, fn ($e) => $e['event'] !== 'delta')));
        $this->assertTrue(Cache::lock("conversation:{$conversation->id}", 1)->get());
    }

    public function test_a_turn_already_running_is_a_plain_conflict(): void
    {
        [$user, $conversation] = $this->conversation(['transcript' => '', 'emotion' => 'happy', 'reply' => 'Да', 'mistakes' => []]);
        Cache::lock("conversation:{$conversation->id}", 40)->get();

        $this->actingAs($user)->postJson("/api/v1/conversations/{$conversation->id}/turns/stream", ['text' => 'Привет'])
            ->assertStatus(409)->assertJsonPath('type', fn ($type) => str_contains((string) $type, 'turn_in_progress'));
    }

    public function test_a_deferred_opening_is_created_at_once_and_streamed_without_spending_a_turn(): void
    {
        $this->seed(DatabaseSeeder::class);
        $ai = new FakeAiClient(['transcript' => '', 'emotion' => 'happy', 'reply' => 'Здравствуйте! Что будете заказывать?', 'mistakes' => []]);
        $this->app->instance(AiClient::class, $ai);
        $this->app->instance(SpeechSynthesizer::class, new FakeSpeech);
        $user = User::factory()->create();
        LearnerProfile::factory()->create(['user_id' => $user->id]);

        $data = $this->actingAs($user)->postJson('/api/v1/conversations', ['scenario_id' => 'cafe', 'defer_opening' => true])
            ->assertCreated()->json('data');

        $this->assertArrayNotHasKey('reply', $data);
        $this->assertSame([], $ai->requests);
        $conversation = Conversation::query()->findOrFail($data['conversation_id']);
        $this->assertSame(0, $conversation->turns()->count());

        $events = $this->stream($user, $conversation, ['opening' => true, 'clips' => true]);

        $this->assertContains('delta', array_column($events, 'event'));
        $this->assertSame('Здравствуйте! Что будете заказывать?', $conversation->turns()->sole()->text);
        // Starting the conversation was the one turn charged.
        $this->assertSame(1, (int) Cache::get(DailyQuota::key('turns', $user->id)));
    }

    public function test_asking_for_the_opening_again_replays_it(): void
    {
        [$user, $conversation] = $this->conversation(['transcript' => '', 'emotion' => 'happy', 'reply' => 'Да', 'mistakes' => []]);
        $opening = $conversation->turns()->sole();
        $ai = new FakeAiClient(['transcript' => '', 'emotion' => 'happy', 'reply' => 'Другое', 'mistakes' => []]);
        $this->app->instance(AiClient::class, $ai);

        $events = $this->stream($user, $conversation, ['opening' => true, 'clips' => true]);

        $this->assertSame([], $ai->requests);
        $this->assertSame(['id' => null, 'reply' => 'Да', 'emotion' => 'happy'], $events[0]['data']);
        $this->assertSame(['event' => 'saved', 'data' => ['id' => $opening->id]], $events[1]);
        $this->assertSame(1, $conversation->turns()->count());
    }

    public function test_a_retried_turn_is_answered_once_and_charged_once(): void
    {
        [$user, $conversation] = $this->conversation(['transcript' => 'Я хочу кофе', 'emotion' => 'happy', 'reply' => self::LONG_REPLY, 'mistakes' => []]);
        $ai = new FakeAiClient(['transcript' => 'Я хочу кофе', 'emotion' => 'happy', 'reply' => self::LONG_REPLY, 'mistakes' => []]);
        $this->app->instance(AiClient::class, $ai);
        $body = ['audio' => base64_encode('fake'), 'mime' => 'audio/mp4', 'clips' => true, 'client_id' => 'turn-1'];

        $first = $this->stream($user, $conversation, $body);
        $again = $this->stream($user, $conversation, $body);

        $this->assertCount(1, $ai->requests);
        $this->assertSame(3, $conversation->turns()->count());
        $this->assertSame(2, (int) Cache::get(DailyQuota::key('turns', $user->id)));
        $this->assertSame(['event' => 'transcript', 'data' => ['text' => 'Я хочу кофе']], $again[0]);
        $only = fn (array $events, string $name) => array_column(array_values(array_filter($events, fn ($e) => $e['event'] === $name)), 'data');
        foreach (['reply', 'audio', 'saved', 'done'] as $name) {
            $this->assertSame($only($first, $name), $only($again, $name), "{$name} events differ");
        }
    }

    public function test_a_used_up_allowance_is_a_plain_quota_problem(): void
    {
        [$user, $conversation] = $this->conversation(['transcript' => '', 'emotion' => 'happy', 'reply' => 'Да', 'mistakes' => []]);
        config(['quota.turns' => 1]);

        $this->actingAs($user)->postJson("/api/v1/conversations/{$conversation->id}/turns/stream", ['text' => 'Привет'])
            ->assertStatus(429)->assertJsonPath('type', 'quota');
        $this->assertTrue(Cache::lock("conversation:{$conversation->id}", 1)->get());
    }

    public function test_a_line_whose_voice_failed_can_be_voiced_again(): void
    {
        [$user, $conversation] = $this->conversation(['transcript' => '', 'emotion' => 'happy', 'reply' => self::LONG_REPLY, 'mistakes' => []]);
        $tutor = $conversation->turns()->sole();
        $tutor->update(['text' => self::LONG_REPLY, 'audio_url' => null]);
        $speech = "/api/v1/conversations/{$conversation->id}/turns/{$tutor->id}/speech";

        $this->actingAs($user)->postJson($speech)
            ->assertOk()->assertExactJson(['data' => ['url' => 'https://cdn.test/a.wav', 'ms' => 1200]]);
        $this->assertSame('https://cdn.test/a.wav', $tutor->fresh()->audio_url);

        // The rest of a line after a piece already heard; never text the tutor did not say.
        $this->actingAs($user)->postJson($speech, ['text' => 'Скажите, пожалуйста, что вы хотите заказать сегодня на обед?'])->assertOk();
        $this->actingAs($user)->postJson($speech, ['text' => 'Say something rude'])
            ->assertStatus(422)->assertJsonPath('type', 'not_in_line');

        $stranger = User::factory()->create();
        $this->actingAs($stranger)->postJson($speech)->assertForbidden();
    }

    public function test_a_voice_that_fails_again_is_a_retryable_problem(): void
    {
        [$user, $conversation] = $this->conversation(['transcript' => '', 'emotion' => 'happy', 'reply' => 'Да', 'mistakes' => []]);
        $this->app->instance(SpeechSynthesizer::class, new class implements SpeechSynthesizer
        {
            public function speak(string $text, string $voice): SpeechClip
            {
                throw new AiUnavailable;
            }
        });
        $tutor = $conversation->turns()->sole();

        $this->actingAs($user)->postJson("/api/v1/conversations/{$conversation->id}/turns/{$tutor->id}/speech")
            ->assertStatus(503)->assertJsonPath('type', 'speech_unavailable');
    }
}
