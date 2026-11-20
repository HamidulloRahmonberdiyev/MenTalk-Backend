<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Filament\Resources\TutorVoices\Tables;

use App\Modules\Speech\Enums\VoiceGender;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class TutorVoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('name.uz')->label('Nomi (uz)'),
                TextColumn::make('name.ru')->label('Nomi (ru)')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('gender')
                    ->label('Jinsi')
                    ->badge()
                    ->formatStateUsing(fn (VoiceGender $state) => $state->label())
                    ->color(fn (VoiceGender $state) => $state === VoiceGender::Female ? 'warning' : 'info'),
                TextColumn::make('voice')->label('Gemini ovozi')->searchable(),
                ToggleColumn::make('active')->label('Faol'),
                TextColumn::make('updated_at')->label('Yangilangan')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('gender')
                    ->label('Jinsi')
                    ->options([VoiceGender::Female->value => VoiceGender::Female->label(), VoiceGender::Male->value => VoiceGender::Male->label()]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
