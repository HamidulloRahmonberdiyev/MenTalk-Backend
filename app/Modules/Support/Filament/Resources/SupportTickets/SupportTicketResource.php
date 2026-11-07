<?php

declare(strict_types=1);

namespace App\Modules\Support\Filament\Resources\SupportTickets;

use App\Modules\Support\Enums\TicketStatus;
use App\Modules\Support\Filament\Resources\SupportTickets\Pages\ListSupportTickets;
use App\Modules\Support\Filament\Resources\SupportTickets\Pages\ViewSupportTicket;
use App\Modules\Support\Filament\Resources\SupportTickets\Schemas\SupportTicketInfolist;
use App\Modules\Support\Filament\Resources\SupportTickets\Tables\SupportTicketsTable;
use App\Modules\Support\Models\SupportTicket;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static ?string $modelLabel = 'murojaat';

    protected static ?string $pluralModelLabel = 'Murojaatlar';

    protected static string|\UnitEnum|null $navigationGroup = 'Foydalanuvchilar';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    /** Unread tickets, so the sidebar says when someone is waiting for an answer. */
    public static function getNavigationBadge(): ?string
    {
        $new = SupportTicket::query()->where('status', TicketStatus::New)->count();

        return $new > 0 ? (string) $new : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function infolist(Schema $schema): Schema
    {
        return SupportTicketInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupportTicketsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupportTickets::route('/'),
            'view' => ViewSupportTicket::route('/{record}'),
        ];
    }
}
