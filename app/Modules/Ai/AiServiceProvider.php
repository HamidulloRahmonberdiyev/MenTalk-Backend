<?php

declare(strict_types=1);

namespace App\Modules\Ai;

use App\Modules\Ai\Contracts\AiClient;
use App\Modules\Ai\Models\AiPrompt;
use App\Modules\Ai\Observers\AiPromptObserver;
use App\Modules\Ai\Services\GeminiClient;
use App\Modules\Ai\Services\GeminiTransport;
use App\Modules\Ai\Services\KeyPool;
use App\Modules\Ai\Services\MeteredAiClient;
use App\Shared\Providers\ModuleServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

final class AiServiceProvider extends ModuleServiceProvider
{
    protected array $observers = [AiPrompt::class => AiPromptObserver::class];

    public function register(): void
    {
        $this->app->singleton(KeyPool::class, fn () => new KeyPool(config('ai.keys'), config('ai.key_cooldown')));
        $this->app->singleton(GeminiTransport::class, fn ($app) => new GeminiTransport($app->make(KeyPool::class), config('ai')));
        $this->app->bind(AiClient::class, fn ($app) => new MeteredAiClient(
            new GeminiClient($app->make(GeminiTransport::class), ['primary' => config('ai.model'), 'fallback' => config('ai.fallback_model')]),
        ));
    }

    public function boot(): void
    {
        parent::boot();

        RateLimiter::for('ai', fn (Request $request) => Limit::perMinute(20)->by($request->user()->id));
    }
}
