<?php

declare(strict_types=1);

namespace App\Filament\Resources\Conversations\Tables;

use App\Enums\ConversationState;
use App\Models\Scenario;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ConversationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user', 'scenario']))
            ->defaultSort('started_at', 'desc')
            ->columns([
                TextColumn::make('user.name')->label('Foydalanuvchi')->searchable()->description(fn ($record) => $record->user->email),
                TextColumn::make('scenario.title.uz')->label('Stsenariy'),
                TextColumn::make('state')->label('Holat')->badge()
                    ->formatStateUsing(fn (ConversationState $state) => $state === ConversationState::Active ? 'Faol' : 'Tugagan')
                    ->color(fn (ConversationState $state) => $state === ConversationState::Active ? 'warning' : 'success'),
                TextColumn::make('learner_turns')->label('Javoblar')->numeric()->sortable(),
                TextColumn::make('duration_sec')->label('Davomiyligi')->sortable()
                    ->formatStateUsing(fn (int $state) => gmdate($state >= 3600 ? 'H:i:s' : 'i:s', $state)),
                TextColumn::make('score')->label('Baho')->placeholder('—')->sortable()->suffix(' / 10'),
                TextColumn::make('started_at')->label('Boshlangan')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('scenario_id')->label('Stsenariy')
                    ->options(fn () => Scenario::query()->orderBy('sort')->get()->mapWithKeys(fn (Scenario $s) => [$s->id => $s->title['uz'] ?? $s->id])->all()),
                SelectFilter::make('state')->label('Holat')
                    ->options([ConversationState::Active->value => 'Faol', ConversationState::Finished->value => 'Tugagan']),
                Filter::make('started')
                    ->label('Sana')
                    ->schema([DatePicker::make('from')->label('Dan'), DatePicker::make('until')->label('Gacha')])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date) => $q->whereDate('started_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date) => $q->whereDate('started_at', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
