<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiPrompts\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class AiPromptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('key')
            ->columns([
                TextColumn::make('key')->label('Kalit')->searchable()->sortable()->badge(),
                TextColumn::make('body')->label('Matn')->limit(90)->wrap()->searchable(),
                TextColumn::make('updated_at')->label('O\'zgargan')->since()->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
