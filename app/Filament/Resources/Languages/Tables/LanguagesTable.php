<?php

declare(strict_types=1);

namespace App\Filament\Resources\Languages\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class LanguagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('briefs'))
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('code')->label('Kod')->badge(),
                TextColumn::make('title.uz')->label('Nomi'),
                TextColumn::make('tutor_name')->label('Tutor'),
                TextColumn::make('briefs_count')->label('Stsenariylar')->numeric(),
                ToggleColumn::make('active')->label('Faol'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
