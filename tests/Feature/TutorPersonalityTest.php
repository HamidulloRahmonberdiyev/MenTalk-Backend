<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Account\Models\LearnerProfile;
use App\Modules\Account\Models\User;
use App\Modules\Ai\Contracts\AiClient;
use App\Modules\Ai\Models\AiPrompt;
use App\Modules\Speech\Contracts\SpeechSynthesizer;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PromptSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeAiClient;
use Tests\Fakes\FakeSpeech;
use Tests\TestCase;

final class TutorPersonalityTest extends TestCase
{
    use RefreshDatabase;

    private const KEYS = ['tutor.system', 'tutor.flow', 'turn.opening', 'turn.audio'];

    private function migration(): Migration
    {
        return require database_path('migrations/2026_11_14_000000_give_the_tutor_a_personality.php');
    }

    private function body(string $key): string
    {
        return AiPrompt::query()->where('key', $key)->value('body');
    }

    public function test_the_tutor_corrects_out_loud_in_the_learners_interface_language(): void
    {
        $this->seed(DatabaseSeeder::class);
        $ai = new FakeAiClient(['transcript' => '', 'emotion' => 'playful', 'reply' => 'Hi there! Ready to order?', 'mistakes' => []]);
        $this->app->instance(AiClient::class, $ai);
        $this->app->instance(SpeechSynthesizer::class, new FakeSpeech);
        $user = User::factory()->create();
        LearnerProfile::factory()->create(['user_id' => $user->id, 'ui_language' => 'ru']);

        $this->actingAs($user)->postJson('/api/v1/conversations', ['scenario_id' => 'cafe'])->assertCreated();

        $request = $ai->requests[0];
        $this->assertStringContainsString('start your reply with a short aside in Russian', $request->system);
        $this->assertStringContainsString('Theatrically grumpy', $request->system);
        $this->assertStringNotContainsString('Never point out mistakes', $request->system);
        // Every placeholder is filled, in the system prompt and in the opening turn.
        $this->assertDoesNotMatchRegularExpression('/\{[a-z_]+\}/', $request->system);
        $this->assertDoesNotMatchRegularExpression('/\{[a-z_]+\}/', json_encode($request->contents, JSON_UNESCAPED_UNICODE));
    }

    public function test_migration_swaps_the_seeded_prompts_and_back(): void
    {
        $this->seed(DatabaseSeeder::class);
        $migration = $this->migration();

        $migration->down();
        $this->assertStringContainsString('Never point out mistakes inside the reply', $this->body('tutor.flow'));

        $migration->up();
        $this->assertSame(PromptSeeder::TUTOR_SYSTEM, $this->body('tutor.system'));
        $this->assertSame(PromptSeeder::TUTOR_FLOW, $this->body('tutor.flow'));
        $this->assertSame(PromptSeeder::TURN_OPENING, $this->body('turn.opening'));
        $this->assertSame(PromptSeeder::TURN_AUDIO, $this->body('turn.audio'));
    }

    public function test_migration_leaves_the_whole_set_alone_when_an_admin_edited_one(): void
    {
        $this->seed(DatabaseSeeder::class);
        $migration = $this->migration();
        $migration->down();
        $before = collect(self::KEYS)->mapWithKeys(fn (string $key) => [$key => $this->body($key)]);
        AiPrompt::query()->where('key', 'tutor.flow')->update(['body' => 'Our own flow rules.']);

        $migration->up();

        $this->assertSame($before['tutor.system'], $this->body('tutor.system'));
        $this->assertSame('Our own flow rules.', $this->body('tutor.flow'));
        $this->assertSame($before['turn.opening'], $this->body('turn.opening'));
    }
}
