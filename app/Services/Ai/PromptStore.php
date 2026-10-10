<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\AiPrompt;
use Illuminate\Support\Facades\Cache;
use LogicException;

final class PromptStore
{
    public function get(string $key): string
    {
        return Cache::rememberForever(
            "prompt:{$key}",
            fn () => AiPrompt::query()->where('key', $key)->where('active', true)->value('body')
                ?? throw new LogicException("Missing prompt [{$key}]"),
        );
    }
}
