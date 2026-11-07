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
        $this->assertSame(['reply', 'audio', 'audio', 'done'], array_values(array_filter($names, fn ($n) => $n !== 'delta')));

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
}
