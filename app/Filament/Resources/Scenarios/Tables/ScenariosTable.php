<?php

declare(strict_types=1);

namespace App\Filament\Resources\Scenarios\Tables;

use App\Models\Scenario;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ScenariosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('conversations')->with('briefs'))
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                ImageColumn::make('image_path')->label('Rasm')->disk('public')->square(),
                TextColumn::make('id')->label('ID')->searchable()->badge(),
                TextColumn::make('title.uz')->label('Nomi (uz)'),
                TextColumn::make('title.en')->label('Nomi (en)')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('briefs.language')->label('Tillar')->badge(),
                TextColumn::make('conversations_count')->label('Suhbatlar')->numeric()->sortable(),
                ToggleColumn::make('active')->label('Faol'),
                TextColumn::make('updated_at')->label('Yangilangan')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('active')->label('Faol'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (Scenario $record) => $record->conversations_count > 0)
                    ->modalDescription('Faqat suhbat tarixi yo\'q stsenariyni o\'chirish mumkin.'),
            ]);
    }
}
