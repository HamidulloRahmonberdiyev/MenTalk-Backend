<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Resources\Users\UserActions;
use App\Http\Middleware\DailyQuota;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

final class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('profile')->withCount('conversations'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('avatar_url')->label('')->circular()->imageSize(36),
                TextColumn::make('name')->label('Ism')->searchable()->description(fn (User $record) => $record->email),
                TextColumn::make('email')->searchable()->hidden(),
                TextColumn::make('profile.level')->label('Daraja')->badge()->placeholder('—'),
                TextColumn::make('profile.xp')->label('XP')->numeric()->placeholder('0'),
                TextColumn::make('profile.streak')->label('Seriya')->suffix(' kun')->placeholder('0'),
                TextColumn::make('conversations_count')->label('Suhbatlar')->numeric()->sortable(),
                TextColumn::make('quota')
                    ->label('Bugungi tur')
                    ->state(fn (User $record) => (int) Cache::get(DailyQuota::key('turns', $record->getKey()), 0).' / '.config('quota.turns')),
                IconColumn::make('is_admin')->label('Admin')->boolean()->toggleable(),
                IconColumn::make('blocked_at')
                    ->label('Bloklangan')
                    ->boolean()
                    ->state(fn (User $record) => $record->isBlocked())
                    ->trueColor('danger')
                    ->falseColor('gray'),
                TextColumn::make('created_at')->label('Ro\'yxatdan o\'tgan')->dateTime('d.m.Y')->sortable()->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('blocked')
                    ->label('Bloklangan')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('blocked_at'),
                        false: fn (Builder $query) => $query->whereNull('blocked_at'),
                    ),
                TernaryFilter::make('is_admin')->label('Admin'),
                TrashedFilter::make()->label('O\'chirilgan akkauntlar'),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    UserActions::resetQuota(),
                    UserActions::block(),
                    UserActions::unblock(),
                ]),
            ]);
    }
}
