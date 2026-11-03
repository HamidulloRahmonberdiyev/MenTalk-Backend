<?php

declare(strict_types=1);

namespace App\Filament\Resources\Announcements\Pages;

use App\Filament\Resources\Announcements\AnnouncementResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;

    /** Saved as a draft; it goes out only when an admin presses "send" on the edit page or the list. */
    protected function handleRecordCreation(array $data): Model
    {
        $record = new (self::getModel())($data);
        $record->forceFill(['created_by' => auth()->id()])->save();

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return self::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
