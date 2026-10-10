<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Problem;
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

    public function handle(Request $request, Closure $next, string $bucket): Response
    {
        $key = self::key($bucket, $request->user()->id);

        Cache::add($key, 0, self::TTL_SECONDS);
        $used = (int) Cache::increment($key);

        return $used > config("quota.{$bucket}") ? Problem::make(429, 'quota') : $next($request);
    }
}
