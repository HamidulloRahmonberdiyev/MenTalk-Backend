<?php

declare(strict_types=1);

namespace App\Shared\Http\Middleware;

use App\Shared\Http\Problem;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

final class DailyQuota
{
    private const TTL_SECONDS = 90_000;

    public static function key(string $bucket, string $userId): string
    {
        return "quota:{$bucket}:{$userId}:".now()->format('Ymd');
    }

    /**
     * Counts one use of today's allowance; false once it is used up. For endpoints that only cost
     * something on some requests (a stored translation is free), in place of the middleware.
     */
    public static function consume(string $bucket, string $userId): bool
    {
        $key = self::key($bucket, $userId);

        Cache::add($key, 0, self::TTL_SECONDS);

        return (int) Cache::increment($key) <= config("quota.{$bucket}");
    }

    public function handle(Request $request, Closure $next, string $bucket): Response
    {
        return self::consume($bucket, $request->user()->id) ? $next($request) : Problem::make(429, 'quota');
    }
}
