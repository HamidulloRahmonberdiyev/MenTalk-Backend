<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Filament\Resources\TutorVoices\Pages;

use App\Modules\Conversation\Filament\Resources\TutorVoices\TutorVoiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListTutorVoices extends ListRecords
{
    protected static string $resource = TutorVoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
