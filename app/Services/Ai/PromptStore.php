<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\AiPrompt;
use Illuminate\Support\Facades\Cache;
use LogicException;

final class PromptStore
{
    public const CACHE_KEY = 'prompts';

    /** Separates a language variant: "tutor.flow@en" is the "tutor.flow" prompt written for English. */
    public const VARIANT_SEPARATOR = '@';

    /**
     * The variant written for `$language` when an admin made one, otherwise the shared prompt. All
     * prompts are cached together: the table is small and read on every AI request.
     */
    public function get(string $key, ?string $language = null): string
    {
        $prompts = Cache::rememberForever(
            self::CACHE_KEY,
            fn () => AiPrompt::query()->where('active', true)->pluck('body', 'key')->all(),
        );

        $variant = $language === null ? null : $prompts[$key.self::VARIANT_SEPARATOR.$language] ?? null;

        return $variant ?? $prompts[$key] ?? throw new LogicException("Missing prompt [{$key}]");
    }

    /** The shared key a stored key belongs to ("tutor.flow@en" → "tutor.flow"). */
    public static function baseKey(string $key): string
    {
        return explode(self::VARIANT_SEPARATOR, $key, 2)[0];
    }
}
