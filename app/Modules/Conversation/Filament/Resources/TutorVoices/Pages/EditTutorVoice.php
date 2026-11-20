<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Filament\Resources\TutorVoices\Pages;

use App\Modules\Conversation\Filament\Resources\TutorVoices\TutorVoiceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditTutorVoice extends EditRecord
{
    protected static string $resource = TutorVoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
