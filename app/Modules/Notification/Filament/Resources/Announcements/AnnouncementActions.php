<?php

declare(strict_types=1);

namespace App\Modules\Notification\Filament\Resources\Announcements;

use App\Modules\Notification\Jobs\SendAnnouncement;
use App\Modules\Notification\Models\Announcement;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

final class AnnouncementActions
{
    public static function send(): Action
    {
        return Action::make('send')
            ->label('Yuborish')
            ->icon(Heroicon::PaperAirplane)
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading("E'lonni yuborish")
            ->modalDescription("E'lon tanlangan foydalanuvchilarning bildirishnomalariga tushadi va telefoniga push bo'lib boradi. Yuborilgandan keyin uni o'zgartirib yoki qaytarib bo'lmaydi.")
            ->visible(fn (Announcement $record) => ! $record->isDispatched())
            ->action(function (Announcement $record): void {
                // Claimed atomically, so a double click (or two admins) sends it once.
                $claimed = Announcement::query()->whereKey($record->getKey())->whereNull('dispatched_at')->update(['dispatched_at' => now()]);

                if ($claimed === 0) {
                    Notification::make()->title("E'lon allaqachon yuborilgan")->warning()->send();

                    return;
                }

                SendAnnouncement::dispatch($record->getKey());
                $record->refresh();

                Notification::make()->title("E'lon yuborilmoqda")->body('Foydalanuvchilar uni bir necha soniyada ko\'radi.')->success()->send();
            });
    }
}
