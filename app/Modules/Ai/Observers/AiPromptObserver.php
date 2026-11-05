<?php

declare(strict_types=1);

namespace App\Modules\Ai\Observers;

use App\Modules\Ai\Models\AiPrompt;
use App\Modules\Ai\Services\PromptStore;
use Illuminate\Support\Facades\Cache;

final class AiPromptObserver
{
    public function saved(AiPrompt $prompt): void
    {
        Cache::forget(PromptStore::CACHE_KEY);
    }

    public function deleted(AiPrompt $prompt): void
    {
        Cache::forget(PromptStore::CACHE_KEY);
    }
}
