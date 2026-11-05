<?php

declare(strict_types=1);

namespace App\Modules\Bootstrap\Observers;

use App\Modules\Bootstrap\Services\BootstrapPayload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/** The bootstrap lists the languages, the scenarios and the languages each can be played in. */
final class ForgetBootstrap
{
    public function saved(Model $model): void
    {
        Cache::forget(BootstrapPayload::CACHE_KEY);
    }

    public function deleted(Model $model): void
    {
        Cache::forget(BootstrapPayload::CACHE_KEY);
    }
}
