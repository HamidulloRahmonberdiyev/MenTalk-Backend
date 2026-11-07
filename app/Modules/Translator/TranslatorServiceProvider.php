<?php

declare(strict_types=1);

namespace App\Modules\Translator;

use App\Shared\Providers\ModuleServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

final class TranslatorServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        // Looser than `ai`: the app translates live while the learner types, and most answers are stored.
        RateLimiter::for('translator', fn (Request $request) => Limit::perMinute(60)->by($request->user()->id));
    }
}
