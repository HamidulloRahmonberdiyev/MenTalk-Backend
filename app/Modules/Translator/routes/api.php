<?php

declare(strict_types=1);

use App\Modules\Translator\Http\Controllers\TranslateController;
use App\Modules\Translator\Http\Controllers\TranslationController;
use Illuminate\Support\Facades\Route;

// Plain text only; kept for app builds that predate `translations`.
Route::post('translate', TranslateController::class)->middleware(['auth:sanctum', 'throttle:ai', 'quota:lookups']);

// The daily quota is charged inside, and only when the AI is asked: stored answers are free.
Route::post('translations', TranslationController::class)->middleware(['auth:sanctum', 'throttle:translator']);
