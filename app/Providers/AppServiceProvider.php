<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\AiClient;
use App\Contracts\IdentityVerifier;
use App\Contracts\SpeechSynthesizer;
use App\Models\AiPrompt;
use App\Models\Scenario;
use App\Observers\AiPromptObserver;
use App\Observers\ScenarioObserver;
use App\Services\Ai\GeminiClient;
use App\Services\Ai\GeminiTransport;
use App\Services\Ai\KeyPool;
use App\Services\Ai\MeteredAiClient;
use App\Services\Auth\GoogleIdentityVerifier;
use App\Services\Speech\CachedSynthesizer;
use App\Services\Speech\GeminiSynthesizer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(KeyPool::class, fn () => new KeyPool(config('ai.keys'), config('ai.key_cooldown')));
        $this->app->singleton(GeminiTransport::class, fn ($app) => new GeminiTransport($app->make(KeyPool::class), config('ai')));
        $this->app->bind(AiClient::class, fn ($app) => new MeteredAiClient(
            new GeminiClient($app->make(GeminiTransport::class), ['primary' => config('ai.model'), 'fallback' => config('ai.fallback_model')]),
        ));
        $this->app->bind(SpeechSynthesizer::class, fn ($app) => new CachedSynthesizer(
            new GeminiSynthesizer($app->make(GeminiTransport::class), config('ai.tts_model')),
        ));
        $this->app->bind(IdentityVerifier::class, fn () => new GoogleIdentityVerifier(config('ai.google_client_ids')));
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        RateLimiter::for('ai', fn (Request $request) => Limit::perMinute(20)->by($request->user()->id));
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        AiPrompt::observe(AiPromptObserver::class);
        Scenario::observe(ScenarioObserver::class);
    }
}
