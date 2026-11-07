<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Account\Models\LearnerProfile;
use App\Modules\Account\Models\User;
use App\Modules\Support\Enums\TicketStatus;
use App\Modules\Support\Filament\Resources\SupportTickets\Pages\ListSupportTickets;
use App\Modules\Support\Filament\Resources\SupportTickets\Pages\ViewSupportTicket;
use App\Modules\Support\Filament\Resources\SupportTickets\SupportTicketResource;
use App\Modules\Support\Models\SupportTicket;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class SupportTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        $user = User::factory()->create();
        LearnerProfile::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    private function ticket(array $overrides = []): array
    {
        return [
            'category' => 'bug',
            'message' => '  Ovoz ba’zan chiqmay qolyapti, suhbat oxirida.  ',
            'contact' => '@learner',
            'app_version' => '1.4.0',
            'platform' => 'android',
            'os_version' => '14',
            'ui_language' => 'uz',
            ...$overrides,
        ];
    }

    public function test_a_learner_sends_a_ticket_and_sees_it_in_their_list(): void
    {
        $user = $this->user();

        $this->actingAs($user)->postJson('/api/v1/support/tickets', $this->ticket())
            ->assertCreated()->assertJsonPath('data.status', 'new');

        $ticket = SupportTicket::query()->sole();
        $this->assertSame($user->id, $ticket->user_id);
        $this->assertSame('Ovoz ba’zan chiqmay qolyapti, suhbat oxirida.', $ticket->message);
        $this->assertSame('android', $ticket->platform);

        $this->actingAs($user)->getJson('/api/v1/support/tickets')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.category', 'bug');
        // Someone else's list is their own.
        $this->actingAs($this->user())->getJson('/api/v1/support/tickets')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_tickets_are_validated(): void
    {
        $this->postJson('/api/v1/support/tickets', $this->ticket())->assertUnauthorized();

        $this->actingAs($this->user())
            ->postJson('/api/v1/support/tickets', $this->ticket(['category' => 'spam', 'message' => 'qisqa']))
            ->assertUnprocessable()->assertJsonValidationErrors(['category', 'message']);
    }

    public function test_sending_is_rate_limited(): void
    {
        config(['support.per_hour' => 2]);
        $user = $this->user();

        $this->actingAs($user)->postJson('/api/v1/support/tickets', $this->ticket())->assertCreated();
        $this->actingAs($user)->postJson('/api/v1/support/tickets', $this->ticket())->assertCreated();
        $this->actingAs($user)->postJson('/api/v1/support/tickets', $this->ticket())->assertTooManyRequests();
    }

    public function test_links_come_from_config(): void
    {
        config(['support.telegram_url' => 'https://t.me/mentalk']);

        $this->actingAs($this->user())->getJson('/api/v1/support')->assertOk()->assertJsonPath('data.telegram_url', 'https://t.me/mentalk');
    }

    public function test_admins_read_tickets_and_opening_one_marks_it_read(): void
    {
        Filament::setCurrentPanel('admin');
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true, 'password' => 'a-long-admin-password'])->save();
        $ticket = SupportTicket::query()->create(['user_id' => $this->user()->id, 'category' => 'idea', 'message' => 'Tungi rejim yana ham qorong‘i bo‘lsin.']);
        $this->actingAs($admin);

        $this->assertSame('1', SupportTicketResource::getNavigationBadge());
        Livewire::test(ListSupportTickets::class)->assertOk()->assertCanSeeTableRecords([$ticket]);
        Livewire::test(ViewSupportTicket::class, ['record' => $ticket->id])->assertOk()
            ->callAction('update', ['status' => 'resolved', 'admin_note' => 'Keyingi versiyada.']);

        $ticket->refresh();
        $this->assertSame(TicketStatus::Resolved, $ticket->status);
        $this->assertSame('Keyingi versiyada.', $ticket->admin_note);
        $this->assertNull(SupportTicketResource::getNavigationBadge());
    }
}
