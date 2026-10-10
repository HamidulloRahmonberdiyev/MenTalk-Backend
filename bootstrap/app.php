<?php

use App\Exceptions\AccountBlocked;
use App\Exceptions\AiRejected;
use App\Exceptions\AiUnavailable;
use App\Http\Middleware\DailyQuota;
use App\Http\Middleware\ETag;
use App\Support\Problem;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['quota' => DailyQuota::class, 'etag' => ETag::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->render(fn (AccountBlocked $e) => Problem::make(403, 'blocked'));
        $exceptions->render(fn (AiUnavailable $e) => Problem::make(503, 'unavailable', ['Retry-After' => 5]));
        $exceptions->render(fn (LockTimeoutException $e) => Problem::make(409, 'turn_in_progress'));
        $exceptions->render(fn (AiRejected $e) => Problem::make(502, 'turn_failed'));
    })->create();
