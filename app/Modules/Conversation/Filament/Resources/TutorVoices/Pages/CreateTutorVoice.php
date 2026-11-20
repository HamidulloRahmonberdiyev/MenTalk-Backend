<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Filament\Resources\TutorVoices\Pages;

use App\Modules\Conversation\Filament\Resources\TutorVoices\TutorVoiceResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateTutorVoice extends CreateRecord
{
    protected static string $resource = TutorVoiceResource::class;
}
