<?php

declare(strict_types=1);

namespace App\Filament\Resources\Announcements\Tables;

use App\Filament\Resources\Announcements\AnnouncementActions;
use App\Models\Announcement;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class AnnouncementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title.uz')->label('Sarlavha')->limit(50),
                TextColumn::make('kind')->label('Turi')->badge()->formatStateUsing(fn ($state) => $state->label()),
                TextColumn::make('audience')->label('Kimga')->placeholder('Hammaga')->formatStateUsing(fn ($state) => strtoupper($state->value)),
                TextColumn::make('status')
                    ->label('Holati')
                    ->badge()
                    ->state(fn (Announcement $record) => match (true) {
                        $record->sent_at !== null => 'Yuborildi',
                        $record->dispatched_at !== null => 'Yuborilmoqda',
                        default => 'Qoralama',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'Yuborildi' => 'success',
                        'Yuborilmoqda' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('recipients')->label('Qabul qildi')->numeric(),
                TextColumn::make('sent_at')->label('Yuborilgan')->since()->placeholder('—'),
                TextColumn::make('created_at')->label('Yaratilgan')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                AnnouncementActions::send(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
