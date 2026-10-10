<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiPrompts\Pages;

use App\Filament\Resources\AiPrompts\AiPromptResource;
use Filament\Resources\Pages\EditRecord;

final class EditAiPrompt extends EditRecord
{
    protected static string $resource = AiPromptResource::class;
}
