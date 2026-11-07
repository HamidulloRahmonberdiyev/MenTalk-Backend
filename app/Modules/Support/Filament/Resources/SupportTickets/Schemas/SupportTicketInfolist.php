<?php

declare(strict_types=1);

namespace App\Modules\Support\Filament\Resources\SupportTickets\Schemas;

use App\Modules\Support\Enums\TicketCategory;
use App\Modules\Support\Enums\TicketStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class SupportTicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make('Xabar')->columnSpan(2)->schema([
                TextEntry::make('message')->hiddenLabel()->prose()->extraAttributes(['style' => 'white-space: pre-wrap']),
                TextEntry::make('admin_note')->label('Ichki izoh')->placeholder('—'),
            ]),
            Section::make('Ma’lumot')->columnSpan(1)->schema([
                TextEntry::make('status')->label('Holat')->badge()
                    ->formatStateUsing(fn (TicketStatus $state) => $state->label())
                    ->color(fn (TicketStatus $state) => $state->color()),
                TextEntry::make('category')->label('Turi')->badge()
                    ->formatStateUsing(fn (TicketCategory $state) => $state->label())
                    ->color(fn (TicketCategory $state) => $state->color()),
                TextEntry::make('user.name')->label('Foydalanuvchi'),
                TextEntry::make('user.email')->label('E-pochta')->copyable(),
                TextEntry::make('contact')->label('Bog‘lanish')->placeholder('—')->copyable(),
                TextEntry::make('created_at')->label('Yuborilgan')->dateTime('d.m.Y H:i'),
                TextEntry::make('platform')->label('Qurilma')->placeholder('—')
                    ->formatStateUsing(fn ($state, $record) => trim("{$state} {$record->os_version}")),
                TextEntry::make('app_version')->label('Ilova versiyasi')->placeholder('—'),
                TextEntry::make('ui_language')->label('Ilova tili')->placeholder('—'),
            ]),
        ]);
    }
}
