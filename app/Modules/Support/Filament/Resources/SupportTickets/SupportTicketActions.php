<?php

declare(strict_types=1);

namespace App\Modules\Support\Filament\Resources\SupportTickets;

use App\Modules\Support\Enums\TicketStatus;
use App\Modules\Support\Models\SupportTicket;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

final class SupportTicketActions
{
    /** Status and an internal note in one dialog: what was done about it, for whoever reads it next. */
    public static function update(): Action
    {
        return Action::make('update')
            ->label('Holat / izoh')
            ->icon(Heroicon::PencilSquare)
            ->fillForm(fn (SupportTicket $record) => ['status' => $record->status->value, 'admin_note' => $record->admin_note])
            ->schema([
                Select::make('status')->label('Holat')->options(TicketStatus::options())->required()->native(false),
                Textarea::make('admin_note')->label('Ichki izoh')->rows(4)->maxLength(2000)
                    ->helperText('Faqat adminlar ko‘radi.'),
            ])
            ->action(function (SupportTicket $record, array $data): void {
                $record->update($data);
                Notification::make()->title('Saqlandi')->success()->send();
            });
    }

    public static function resolve(): Action
    {
        return Action::make('resolve')
            ->label('Hal qilindi')
            ->icon(Heroicon::CheckCircle)
            ->color('success')
            ->visible(fn (SupportTicket $record) => $record->status !== TicketStatus::Resolved)
            ->action(fn (SupportTicket $record) => $record->update(['status' => TicketStatus::Resolved]));
    }
}
