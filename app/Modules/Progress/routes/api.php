<?php

declare(strict_types=1);

use App\Modules\Progress\Http\Controllers\CalendarController;
use App\Modules\Progress\Http\Controllers\StatsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('stats', StatsController::class);
    Route::get('progress/calendar', CalendarController::class);
});
