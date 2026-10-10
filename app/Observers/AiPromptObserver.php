<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AiPrompt;
use Illuminate\Support\Facades\Cache;

final class AiPromptObserver
{
    public function saved(AiPrompt $prompt): void
    {
        Cache::forget("prompt:{$prompt->key}");
    }

    public function deleted(AiPrompt $prompt): void
    {
        Cache::forget("prompt:{$prompt->key}");
    }
}
