<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiPrompts\Pages;

use App\Filament\Resources\AiPrompts\AiPromptResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListAiPrompts extends ListRecords
{
    protected static string $resource = AiPromptResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Til uchun variant')];
    }
}
