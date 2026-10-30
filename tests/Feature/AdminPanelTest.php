<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LanguageCatalog;
use App\Filament\Resources\AiPrompts\Pages\CreateAiPrompt;
use App\Filament\Resources\AiPrompts\Pages\EditAiPrompt;
use App\Filament\Resources\Conversations\Pages\ListConversations;
use App\Filament\Resources\Languages\Pages\CreateLanguage;
use App\Filament\Resources\Languages\Pages\ListLanguages;
use App\Filament\Resources\Scenarios\Pages\CreateScenario;
use App\Filament\Resources\Scenarios\Pages\ListScenarios;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Http\Middleware\DailyQuota;
use App\Models\AiPrompt;
use App\Models\Conversation;
use App\Models\Scenario;
use App\Models\User;
use App\Services\Ai\PromptStore;
use Database\Seeders\AdminSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\LanguageSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

final class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true, 'password' => 'a-long-admin-password'])->save();

        return $admin;
    }

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_admin_seeder_is_idempotent_and_can_log_in(): void
    {
        config(['admin.seed.email' => 'boss@mentalk.test', 'admin.seed.password' => 'a-long-seed-password']);

        $this->seed(AdminSeeder::class);
        $this->seed(AdminSeeder::class);

        $admin = User::query()->where('email', 'boss@mentalk.test')->sole();
        $this->assertTrue($admin->is_admin);
        $this->assertTrue(Hash::check('a-long-seed-password', $admin->password));
        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_learners_cannot_open_the_panel(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }

    public function test_admins_see_the_dashboard(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->actingAs($this->admin())->get('/admin')->assertOk();
    }

    public function test_blocked_admins_are_locked_out(): void
    {
        $admin = $this->admin();
        $admin->forceFill(['blocked_at' => now()])->save();

        $this->actingAs($admin)->get('/admin')->assertForbidden();
    }

    public function test_resource_lists_render(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = $this->admin();
        Conversation::factory()->for(Scenario::query()->firstOrFail())->create();
        $this->actingAs($admin);

        Livewire::test(ListScenarios::class)->assertOk();
        Livewire::test(ListUsers::class)->assertOk();
        Livewire::test(ListConversations::class)->assertOk();
    }

    public function test_scenario_can_be_created_and_clears_the_bootstrap_cache(): void
    {
        $this->seed(LanguageSeeder::class);
        $this->actingAs($this->admin());
        $this->getJson('/api/v1/bootstrap')->assertJsonCount(0, 'scenarios');

        Livewire::test(CreateScenario::class)
            ->fillForm([
                'id' => 'hotel', 'icon' => 'bed', 'tint' => '#3b82f6', 'sort' => 5, 'active' => true,
                'gradient' => ['#93c5fd', '#3b82f6'],
                'title' => ['uz' => 'Mehmonxona', 'ru' => 'Отель', 'en' => 'Hotel'],
                'subtitle' => ['uz' => 'Xona band qiling', 'ru' => 'Забронируйте номер', 'en' => 'Book a room'],
                'briefs' => [['language' => 'en', 'brief' => 'You are {tutor}, a hotel receptionist.', 'next_topics' => 'dates, price']],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->getJson('/api/v1/bootstrap')->assertJsonPath('scenarios.0.id', 'hotel')->assertJsonPath('scenarios.0.languages', ['en']);
    }

    public function test_prompt_edit_keeps_required_placeholders_and_refreshes_cache(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs($this->admin());
        $prompt = AiPrompt::query()->where('key', 'tutor.system')->firstOrFail();

        Livewire::test(EditAiPrompt::class, ['record' => $prompt->getKey()])
            ->fillForm(['body' => 'No placeholders at all'])
            ->call('save')
            ->assertHasFormErrors(['body']);

        Livewire::test(EditAiPrompt::class, ['record' => $prompt->getKey()])
            ->fillForm(['body' => $prompt->body.' Be concise.'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertStringEndsWith('Be concise.', app(PromptStore::class)->get('tutor.system'));
    }

    public function test_a_language_variant_overrides_the_shared_prompt_and_can_be_deleted(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs($this->admin());

        Livewire::test(CreateAiPrompt::class)
            ->fillForm(['base_key' => 'hint', 'language' => 'en', 'body' => 'Suggest one short British English sentence.'])
            ->call('create')
            ->assertHasNoFormErrors();

        $store = app(PromptStore::class);
        $this->assertSame('Suggest one short British English sentence.', $store->get('hint', 'en'));
        $this->assertStringContainsString('{language}', $store->get('hint', 'ru'));

        Livewire::test(CreateAiPrompt::class)
            ->fillForm(['base_key' => 'hint', 'language' => 'en', 'body' => 'Again'])
            ->call('create')
            ->assertHasFormErrors(['language']);

        $variant = AiPrompt::query()->where('key', 'hint@en')->sole();
        Livewire::test(EditAiPrompt::class, ['record' => $variant->getKey()])->callAction('delete');

        $this->assertStringContainsString('{language}', $store->get('hint', 'en'));
    }

    public function test_shared_prompts_must_name_the_language_through_a_placeholder(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs($this->admin());
        $prompt = AiPrompt::query()->where('key', 'hint')->firstOrFail();

        Livewire::test(EditAiPrompt::class, ['record' => $prompt->getKey()])
            ->fillForm(['body' => 'Suggest one short Russian sentence.'])
            ->call('save')
            ->assertHasFormErrors(['body']);
    }

    public function test_admin_adds_a_language_without_a_deploy(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs($this->admin());
        $catalog = app(LanguageCatalog::class);
        $this->assertFalse($catalog->isAvailable('de'));

        Livewire::test(ListLanguages::class)->assertOk();
        Livewire::test(CreateLanguage::class)
            ->fillForm([
                'code' => 'de', 'name' => 'German', 'tutor_name' => 'Lena', 'sort' => 2, 'active' => true,
                'title' => ['uz' => 'Nemis tili', 'ru' => 'Немецкий', 'en' => 'German'],
                'basic_words' => 'hallo, ja, nein', 'dictionary_form' => 'infinitive, nominative singular with article',
                'follow_ups' => '"Und Sie?"',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('Lena', $catalog->get('de')->tutorName);
        $this->getJson('/api/v1/bootstrap')->assertJsonPath('languages.2.code', 'de');
    }

    public function test_admin_can_block_a_user_and_revoke_tokens(): void
    {
        $this->actingAs($this->admin());
        $learner = User::factory()->create();
        $learner->createToken('phone');

        Livewire::test(ListUsers::class)->callTableAction('block', $learner);

        $this->assertTrue($learner->refresh()->isBlocked());
        $this->assertSame(0, $learner->tokens()->count());
    }

    public function test_admin_can_reset_a_quota_and_grant_admin_access(): void
    {
        $this->actingAs($this->admin());
        $learner = User::factory()->create();
        Cache::put(DailyQuota::key('turns', $learner->id), 299);

        Livewire::test(ListUsers::class)->callTableAction('resetQuota', $learner);
        $this->assertNull(Cache::get(DailyQuota::key('turns', $learner->id)));

        Livewire::test(EditUser::class, ['record' => $learner->getKey()])
            ->fillForm(['is_admin' => true, 'password' => 'another-long-password'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($learner->refresh()->is_admin);
    }
}
