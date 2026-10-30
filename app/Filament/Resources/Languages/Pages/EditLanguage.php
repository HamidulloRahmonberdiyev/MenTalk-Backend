<?php

declare(strict_types=1);

namespace App\Filament\Resources\Languages\Pages;

use App\Filament\Resources\Languages\LanguageResource;
use Filament\Resources\Pages\EditRecord;

final class EditLanguage extends EditRecord
{
    protected static string $resource = LanguageResource::class;
}
