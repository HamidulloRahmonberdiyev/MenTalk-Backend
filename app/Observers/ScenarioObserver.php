<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Scenario;
use Illuminate\Support\Facades\Cache;

final class ScenarioObserver
{
    public function saved(Scenario $scenario): void
    {
        Cache::forget('bootstrap');
    }

    public function deleted(Scenario $scenario): void
    {
        Cache::forget('bootstrap');
    }
}
