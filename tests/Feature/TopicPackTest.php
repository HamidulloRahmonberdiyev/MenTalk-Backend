<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Account\Models\LearnerProfile;
use App\Modules\Account\Models\User;
use App\Modules\Ai\Contracts\AiClient;
use App\Modules\Conversation\Models\Scenario;
use App\Modules\Conversation\Models\ScenarioBrief;
use App\Modules\Speech\Contracts\SpeechSynthesizer;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\TopicPackSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeAiClient;
use Tests\Fakes\FakeSpeech;
use Tests\TestCase;

final class TopicPackTest extends TestCase
{
    use RefreshDatabase;

    /** @return list<string> */
    private function packIds(): array
    {
        return array_column(TopicPackSeeder::topics(), 'id');
    }

    public function test_the_pack_adds_fifteen_topics_after_the_existing_path_in_both_languages(): void
    {
        $this->seed(DatabaseSeeder::class);
        $pack = $this->packIds();

        $this->assertCount(15, $pack);
        $this->assertSame($pack, array_unique($pack));
        // In the pack's order, every one after the topics that were already on the path.
        $this->assertSame($pack, Scenario::query()->whereIn('id', $pack)->orderBy('sort')->pluck('id')->all());
        $this->assertGreaterThan(
            Scenario::query()->whereNotIn('id', $pack)->max('sort'),
            Scenario::query()->whereIn('id', $pack)->min('sort'),
        );

        foreach (Scenario::query()->with('briefs')->whereIn('id', $pack)->get() as $scenario) {
            $this->assertTrue($scenario->active, $scenario->id);
            $this->assertTrue($scenario->isPlayableIn('ru'), $scenario->id);
            $this->assertTrue($scenario->isPlayableIn('en'), $scenario->id);
            $this->assertLessThanOrEqual(24, strlen($scenario->id));
            $this->assertCount(2, $scenario->gradient);

            foreach (['uz', 'ru', 'en'] as $language) {
                $this->assertNotEmpty($scenario->title[$language] ?? null, "{$scenario->id} title {$language}");
                $this->assertNotEmpty($scenario->subtitle[$language] ?? null, "{$scenario->id} subtitle {$language}");
            }

            foreach ($scenario->briefs as $brief) {
                // The tutor is named by the language, never in the brief itself.
                $this->assertStringContainsString('You are {tutor}', $brief->brief, "{$scenario->id} {$brief->language}");
                $this->assertNotEmpty($brief->next_topics);
            }
        }

        $listed = collect($this->getJson('/api/v1/bootstrap')->assertOk()->json('scenarios'))->keyBy('id');
        $this->assertSame(['ru', 'en'], $listed['interview']['languages']);
    }

    public function test_running_it_again_keeps_what_admins_changed_and_adds_only_what_is_missing(): void
    {
        $this->seed(DatabaseSeeder::class);
        Scenario::query()->findOrFail('hotel')->update(['title' => ['uz' => 'Hotel', 'ru' => 'Отель', 'en' => 'Hotel'], 'active' => false]);
        ScenarioBrief::query()->where('scenario_id', 'banking')->where('language', 'en')->update(['brief' => 'Edited by an admin.']);
        Scenario::query()->findOrFail('lost')->delete();
        $briefs = ScenarioBrief::query()->count();

        $this->seed(TopicPackSeeder::class);
        $this->seed(TopicPackSeeder::class);

        $hotel = Scenario::query()->findOrFail('hotel');
        $this->assertSame('Отель', $hotel->title['ru']);
        $this->assertFalse($hotel->active);
        $this->assertSame('Edited by an admin.', ScenarioBrief::query()->where('scenario_id', 'banking')->where('language', 'en')->value('brief'));
        // The removed topic is back, once, at the end of the path.
        $this->assertSame(Scenario::query()->max('sort'), Scenario::query()->findOrFail('lost')->sort);
        $this->assertSame($briefs + 2, ScenarioBrief::query()->count());
    }

    public function test_a_new_topic_starts_in_english_with_its_london_brief(): void
    {
        $this->seed(DatabaseSeeder::class);
        $ai = new FakeAiClient(['transcript' => '', 'emotion' => 'happy', 'reply' => 'Hello! Lovely to meet you.', 'mistakes' => []]);
        $this->app->instance(AiClient::class, $ai);
        $this->app->instance(SpeechSynthesizer::class, new FakeSpeech);
        $user = User::factory()->create();
        LearnerProfile::factory()->create(['user_id' => $user->id, 'target_language' => 'ru']);

        $this->actingAs($user)->postJson('/api/v1/conversations', ['scenario_id' => 'interview', 'target_language' => 'en'])
            ->assertCreated()
            ->assertJsonPath('data.target_language', 'en');

        $system = $ai->requests[0]->system;
        $this->assertStringContainsString('company office in London', $system);
        $this->assertStringContainsString('You are Emma', $system);
        $this->assertStringNotContainsString('Moscow', $system);
    }
}
