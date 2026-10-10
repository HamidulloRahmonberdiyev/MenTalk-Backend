<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserActions;
use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

final class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            UserActions::resetQuota(),
            UserActions::block(),
            UserActions::unblock(),
        ];
    }

    /** is_admin and password are not mass assignable on purpose, so they are set explicitly. */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->forceFill(Arr::only($data, ['is_admin', 'password']))->save();

        return $record;
    }
}
