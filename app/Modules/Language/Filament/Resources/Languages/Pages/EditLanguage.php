<?php

declare(strict_types=1);

namespace App\Modules\Language\Filament\Resources\Languages\Pages;

use App\Modules\Language\Filament\Resources\Languages\LanguageResource;
use Filament\Resources\Pages\EditRecord;

final class EditLanguage extends EditRecord
{
    protected static string $resource = LanguageResource::class;
}
