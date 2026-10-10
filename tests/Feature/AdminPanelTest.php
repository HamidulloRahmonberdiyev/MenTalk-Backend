<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\AiPrompts\Pages\EditAiPrompt;
use App\Filament\Resources\Conversations\Pages\ListConversations;
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
        $this->actingAs($this->admin());
        $this->getJson('/api/v1/bootstrap')->assertJsonCount(0, 'scenarios');

        Livewire::test(CreateScenario::class)
            ->fillForm([
                'id' => 'hotel', 'icon' => 'bed', 'tint' => '#3b82f6', 'sort' => 5, 'active' => true,
                'gradient' => ['#93c5fd', '#3b82f6'],
                'title' => ['uz' => 'Mehmonxona', 'ru' => 'Отель', 'en' => 'Hotel'],
                'subtitle' => ['uz' => 'Xona band qiling', 'ru' => 'Забронируйте номер', 'en' => 'Book a room'],
                'brief' => 'You are a hotel receptionist.', 'next_topics' => 'dates, price',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->getJson('/api/v1/bootstrap')->assertJsonPath('scenarios.0.id', 'hotel');
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
