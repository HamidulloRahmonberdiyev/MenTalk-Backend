<?php

declare(strict_types=1);

use App\Modules\Progress\Http\Controllers\StatsController;
use Illuminate\Support\Facades\Route;

Route::get('stats', StatsController::class)->middleware('auth:sanctum');
