<?php

declare(strict_types=1);

namespace App\Modules\Notification\Filament\Resources\Announcements\Pages;

use App\Modules\Notification\Filament\Resources\Announcements\AnnouncementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListAnnouncements extends ListRecords
{
    protected static string $resource = AnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
