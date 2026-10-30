<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiPrompts\Pages;

use App\Filament\Resources\AiPrompts\AiPromptResource;
use App\Services\Ai\PromptStore;
use Filament\Resources\Pages\CreateRecord;

/** Creates a language variant of a shared prompt. */
final class CreateAiPrompt extends CreateRecord
{
    protected static string $resource = AiPromptResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [
            'key' => $data['base_key'].PromptStore::VARIANT_SEPARATOR.$data['language'],
            'body' => $data['body'],
            'active' => true,
        ];
    }
}
