<?php

use App\Modules\Notification\Models\DatabaseNotification;
use App\Modules\Translator\Models\Translation;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Hourly, because "19:00" comes at a different hour in every learner's timezone.
Schedule::command('notifications:remind')->hourly()->withoutOverlapping()->onOneServer();
Schedule::command('model:prune', ['--model' => [DatabaseNotification::class, Translation::class]])->dailyAt('03:30')->onOneServer();
