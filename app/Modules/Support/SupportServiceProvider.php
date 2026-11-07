<?php

declare(strict_types=1);

namespace App\Modules\Support;

use App\Shared\Providers\ModuleServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/** Learners' messages to the team (bugs, ideas, questions), kept for the admin panel. */
final class SupportServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        RateLimiter::for('support', fn (Request $request) => Limit::perHour((int) config('support.per_hour'))->by($request->user()?->id ?: $request->ip()));
    }
}
