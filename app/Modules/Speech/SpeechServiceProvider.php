<?php

declare(strict_types=1);

namespace App\Modules\Speech;

use App\Modules\Ai\Services\GeminiTransport;
use App\Modules\Speech\Contracts\SpeechSynthesizer;
use App\Modules\Speech\Services\CachedSynthesizer;
use App\Modules\Speech\Services\GeminiSynthesizer;
use App\Shared\Providers\ModuleServiceProvider;

final class SpeechServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SpeechSynthesizer::class, fn ($app) => new CachedSynthesizer(
            new GeminiSynthesizer($app->make(GeminiTransport::class), config('ai.tts_model')),
        ));
    }
}
