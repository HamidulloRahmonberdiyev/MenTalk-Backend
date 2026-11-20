<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Account\Models\LearnerProfile;
use App\Modules\Account\Models\User;
use App\Modules\Ai\Contracts\AiClient;
use App\Modules\Conversation\Filament\Resources\TutorVoices\Pages\CreateTutorVoice;
use App\Modules\Conversation\Models\Conversation;
use App\Modules\Conversation\Models\TutorVoice;
use App\Modules\Language\Models\Language;
use App\Modules\Language\Services\CachedLanguageCatalog;
use App\Modules\Speech\Contracts\SpeechSynthesizer;
use App\Modules\Speech\DTO\SpeechClip;
use App\Modules\Speech\Enums\VoiceGender;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use RuntimeException;
use Tests\Fakes\FakeAiClient;
use Tests\TestCase;

final class TutorVoiceTest extends TestCase
{
    use RefreshDatabase;

    /** Every line spoken, and the voice it was spoken with. */
    private SpeechSynthesizer $speech;

    private FakeAiClient $ai;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->ai = new FakeAiClient(['transcript' => '', 'emotion' => 'happy', 'reply' => 'Здравствуйте! Что будете заказывать?', 'mistakes' => []]);
        $this->app->instance(AiClient::class, $this->ai);
        $this->speech = new class implements SpeechSynthesizer
        {
            /** @var list<array{string, string}> */
            public array $spoken = [];

            public bool $fails = false;

            public function speak(string $text, string $voice): SpeechClip
            {
                if ($this->fails) {
                    throw new RuntimeException('TTS down');
                }
                $this->spoken[] = [$text, $voice];

                return new SpeechClip('https://cdn.test/'.count($this->spoken).'.wav', 1500);
            }
        };
        $this->app->instance(SpeechSynthesizer::class, $this->speech);

        $this->user = User::factory()->create();
        LearnerProfile::factory()->create(['user_id' => $this->user->id, 'target_language' => 'ru']);
    }

    private function voice(string $gemini): TutorVoice
    {
        return TutorVoice::query()->where('voice', $gemini)->firstOrFail();
    }

    /** Starts a conversation the way the app does, and streams the tutor's first line. */
    private function start(array $body): Conversation
    {
        $id = $this->actingAs($this->user)
            ->postJson('/api/v1/conversations', ['scenario_id' => 'cafe', 'defer_opening' => true] + $body)
            ->assertCreated()
            ->json('data.conversation_id');
        $conversation = Conversation::query()->findOrFail($id);

        $this->actingAs($this->user)
            ->post("/api/v1/conversations/{$id}/turns/stream", ['opening' => true, 'clips' => true], ['Accept' => 'text/event-stream'])
            ->assertOk()
            ->streamedContent();

        return $conversation;
    }

    public function test_the_app_gets_three_female_and_three_male_voices_with_the_default_marked(): void
    {
        Language::query()->where('code', 'ru')->update(['voice' => 'Achird']);
        Cache::forget(CachedLanguageCatalog::CACHE_KEY);

        $voices = $this->actingAs($this->user)->getJson('/api/v1/tutor-voices?language=ru')->assertOk()->json('data');

        $this->assertCount(3, array_filter($voices, fn (array $v) => $v['gender'] === 'female'));
        $this->assertCount(3, array_filter($voices, fn (array $v) => $v['gender'] === 'male'));
        $this->assertSame(['uz' => 'Do‘stona', 'ru' => 'Дружелюбный', 'en' => 'Friendly'], $voices[3]['name']);
        $this->assertSame([$this->voice('Achird')->id], array_column(array_filter($voices, fn (array $v) => $v['default']), 'id'));
    }

    public function test_switched_off_voices_are_not_offered(): void
    {
        $this->voice('Iapetus')->update(['active' => false]);

        $ids = array_column($this->actingAs($this->user)->getJson('/api/v1/tutor-voices')->assertOk()->json('data'), 'id');

        $this->assertNotContains($this->voice('Iapetus')->id, $ids);
        $this->assertCount(5, $ids);
    }

    public function test_a_male_voice_is_a_male_tutor_for_the_whole_conversation(): void
    {
        $conversation = $this->start(['voice' => $this->voice('Achird')->id]);

        $this->assertSame('Achird', $conversation->voice);
        $this->assertNotEmpty($this->speech->spoken);
        $this->assertSame(['Achird'], array_values(array_unique(array_column($this->speech->spoken, 1))));

        $system = $this->ai->requests[0]->system;
        $this->assertStringContainsString('You, Ivan, are a man', $system);
        $this->assertStringNotContainsString('Anna', $system);
    }

    public function test_without_a_choice_the_tutor_is_the_language_default(): void
    {
        $conversation = $this->start([]);

        $this->assertNull($conversation->voice);
        $this->assertSame([config('ai.voice')], array_values(array_unique(array_column($this->speech->spoken, 1))));
        $this->assertStringContainsString('You, Anna, are a woman', $this->ai->requests[0]->system);
    }

    public function test_a_voice_that_is_gone_or_switched_off_falls_back_to_the_default(): void
    {
        $this->voice('Algieba')->update(['active' => false]);

        $this->assertNull($this->start(['voice' => $this->voice('Algieba')->id])->voice);
        $this->assertNull($this->start(['voice' => 999_999])->voice);
    }

    public function test_a_voice_can_be_heard_before_it_is_picked(): void
    {
        $url = $this->actingAs($this->user)
            ->postJson("/api/v1/tutor-voices/{$this->voice('Iapetus')->id}/sample", ['language' => 'en'])
            ->assertOk()
            ->json('data.url');

        $this->assertNotEmpty($url);
        $this->assertSame([["Hi! I'm James. Let's have a little chat in English.", 'Iapetus']], $this->speech->spoken);
    }

    public function test_a_sample_is_in_the_language_the_learner_studies_by_default(): void
    {
        $this->actingAs($this->user)->postJson("/api/v1/tutor-voices/{$this->voice('Sulafat')->id}/sample")->assertOk();

        $this->assertSame([['Привет! Меня зовут Anna. Давай немного поговорим по-русски.', 'Sulafat']], $this->speech->spoken);
    }

    public function test_a_switched_off_voice_has_no_sample_and_a_failed_one_can_be_retried(): void
    {
        $voice = $this->voice('Achernar');
        $voice->update(['active' => false]);
        $this->actingAs($this->user)->postJson("/api/v1/tutor-voices/{$voice->id}/sample")->assertNotFound();

        $voice->update(['active' => true]);
        $this->speech->fails = true;
        $this->actingAs($this->user)->postJson("/api/v1/tutor-voices/{$voice->id}/sample")
            ->assertStatus(503)
            ->assertHeader('Retry-After', '3');
    }

    public function test_admins_add_voices_and_the_gender_follows_the_gemini_voice(): void
    {
        Filament::setCurrentPanel('admin');
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true, 'password' => 'a-long-admin-password'])->save();
        $this->actingAs($admin);

        Livewire::test(CreateTutorVoice::class)
            ->fillForm([
                'voice' => 'Charon',
                'sort' => 7,
                'active' => true,
                'name' => ['uz' => 'Jiddiy', 'ru' => 'Серьёзный', 'en' => 'Serious'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(VoiceGender::Male, $this->voice('Charon')->gender);
    }
}
