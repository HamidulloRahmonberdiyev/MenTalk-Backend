<?php

use App\Shared\Http\Middleware\DailyQuota;
use App\Shared\Http\Middleware\ETag;
use App\Shared\Http\Problem;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
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
        $exceptions->render(fn (LockTimeoutException $e) => Problem::make(409, 'turn_in_progress'));
    })->create();
