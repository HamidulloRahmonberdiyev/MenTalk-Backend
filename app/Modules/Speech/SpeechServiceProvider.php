<?php

declare(strict_types=1);

namespace App\Modules\Speech;

use App\Modules\Ai\Services\GeminiTransport;
use App\Modules\Speech\Contracts\SpeechSynthesizer;
use App\Modules\Speech\Services\CachedSynthesizer;
use App\Modules\Speech\Services\GeminiSynthesizer;
use App\Modules\Speech\Services\ResilientSynthesizer;
use App\Shared\Providers\ModuleServiceProvider;

final class SpeechServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SpeechSynthesizer::class, function ($app) {
            $transport = $app->make(GeminiTransport::class);
            $fallback = config('ai.tts_fallback_model');

            // Retries sit inside the cache, so only a clip that finally worked is remembered.
            return new CachedSynthesizer(new ResilientSynthesizer(
                new GeminiSynthesizer($transport, config('ai.tts_model')),
                $fallback ? new GeminiSynthesizer($transport, $fallback) : null,
            ));
        });
    }
}
