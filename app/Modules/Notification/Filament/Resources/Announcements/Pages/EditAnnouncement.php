<?php

declare(strict_types=1);

namespace App\Modules\Notification\Filament\Resources\Announcements\Pages;

use App\Modules\Notification\Filament\Resources\Announcements\AnnouncementActions;
use App\Modules\Notification\Filament\Resources\Announcements\AnnouncementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditAnnouncement extends EditRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AnnouncementActions::send()->after(fn () => $this->redirect(self::getResource()::getUrl('index'))),
            DeleteAction::make(),
        ];
    }
}
