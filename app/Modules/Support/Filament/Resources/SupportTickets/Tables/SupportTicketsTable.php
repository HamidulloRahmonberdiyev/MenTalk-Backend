<?php

declare(strict_types=1);

namespace App\Modules\Support\Filament\Resources\SupportTickets\Tables;

use App\Modules\Support\Enums\TicketCategory;
use App\Modules\Support\Enums\TicketStatus;
use App\Modules\Support\Filament\Resources\SupportTickets\SupportTicketActions;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class SupportTicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Sana')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('user.name')->label('Foydalanuvchi')->searchable()
                    ->description(fn ($record) => $record->user?->email),
                TextColumn::make('category')->label('Turi')->badge()
                    ->formatStateUsing(fn (TicketCategory $state) => $state->label())
                    ->color(fn (TicketCategory $state) => $state->color()),
                TextColumn::make('message')->label('Xabar')->limit(80)->wrap()->searchable(),
                TextColumn::make('status')->label('Holat')->badge()
                    ->formatStateUsing(fn (TicketStatus $state) => $state->label())
                    ->color(fn (TicketStatus $state) => $state->color()),
                TextColumn::make('platform')->label('Qurilma')->placeholder('—')
                    ->formatStateUsing(fn ($state, $record) => trim("{$state} {$record->os_version}"))
                    ->description(fn ($record) => $record->app_version ? "v{$record->app_version}" : null)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Holat')->options(TicketStatus::options()),
                SelectFilter::make('category')->label('Turi')
                    ->options(array_combine(
                        array_column(TicketCategory::cases(), 'value'),
                        array_map(fn (TicketCategory $category) => $category->label(), TicketCategory::cases()),
                    )),
            ])
            ->recordActions([
                ViewAction::make(),
                SupportTicketActions::resolve(),
            ]);
    }
}
