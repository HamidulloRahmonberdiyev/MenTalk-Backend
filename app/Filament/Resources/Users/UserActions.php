<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users;

use App\Http\Middleware\DailyQuota;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;

/** Row/header actions shared by the users table and the edit page. */
final class UserActions
{
    public static function block(): Action
    {
        return Action::make('block')
            ->label('Bloklash')
            ->icon(Heroicon::NoSymbol)
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription('Foydalanuvchi tizimdan chiqariladi va qayta kira olmaydi.')
            ->visible(fn (User $record) => ! $record->isBlocked() && $record->getKey() !== auth()->id())
            ->action(function (User $record): void {
                $record->forceFill(['blocked_at' => now()])->save();
                $record->tokens()->delete();

                Notification::make()->title('Foydalanuvchi bloklandi')->success()->send();
            });
    }

    public static function unblock(): Action
    {
        return Action::make('unblock')
            ->label('Blokdan chiqarish')
            ->icon(Heroicon::LockOpen)
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (User $record) => $record->isBlocked())
            ->action(function (User $record): void {
                $record->forceFill(['blocked_at' => null])->save();

                Notification::make()->title('Blok olib tashlandi')->success()->send();
            });
    }

    public static function resetQuota(): Action
    {
        return Action::make('resetQuota')
            ->label('Kvotani tiklash')
            ->icon(Heroicon::ArrowPath)
            ->requiresConfirmation()
            ->modalDescription('Bugungi suhbat va qidiruv hisoblagichlari nolga tushadi.')
            ->action(function (User $record): void {
                foreach (array_keys(config('quota')) as $bucket) {
                    Cache::forget(DailyQuota::key($bucket, $record->getKey()));
                }

                Notification::make()->title('Kvota tiklandi')->success()->send();
            });
    }
}
