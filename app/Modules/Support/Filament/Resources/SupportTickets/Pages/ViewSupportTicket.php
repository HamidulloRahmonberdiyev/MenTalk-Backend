<?php

declare(strict_types=1);

namespace App\Modules\Support\Filament\Resources\SupportTickets\Pages;

use App\Modules\Support\Enums\TicketStatus;
use App\Modules\Support\Filament\Resources\SupportTickets\SupportTicketActions;
use App\Modules\Support\Filament\Resources\SupportTickets\SupportTicketResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewSupportTicket extends ViewRecord
{
    protected static string $resource = SupportTicketResource::class;

    /** Opening a new ticket is reading it: it moves on to "in progress" so the badge counts only unread ones. */
    public function mount(int|string $record): void
    {
        parent::mount($record);

        if ($this->record->status === TicketStatus::New) {
            $this->record->update(['status' => TicketStatus::InProgress]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            SupportTicketActions::update(),
            SupportTicketActions::resolve(),
        ];
    }
}
