<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\ScenarioBrief;
use Illuminate\Support\Facades\Cache;

/** The bootstrap tells the app which languages each scenario can be played in. */
final class ScenarioBriefObserver
{
    public function saved(ScenarioBrief $brief): void
    {
        Cache::forget('bootstrap');
    }

    public function deleted(ScenarioBrief $brief): void
    {
        Cache::forget('bootstrap');
    }
}
