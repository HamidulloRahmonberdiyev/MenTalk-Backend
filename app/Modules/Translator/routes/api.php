<?php

declare(strict_types=1);

use App\Modules\Translator\Http\Controllers\TranslateController;
use Illuminate\Support\Facades\Route;

Route::post('translate', TranslateController::class)->middleware(['auth:sanctum', 'throttle:ai', 'quota:lookups']);
