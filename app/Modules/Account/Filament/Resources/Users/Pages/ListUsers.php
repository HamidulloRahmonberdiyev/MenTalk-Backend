<?php

declare(strict_types=1);

namespace App\Modules\Account\Filament\Resources\Users\Pages;

use App\Modules\Account\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\ListRecords;

final class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;
}
