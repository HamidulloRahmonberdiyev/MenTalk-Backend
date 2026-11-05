<?php

declare(strict_types=1);

use App\Modules\Vocabulary\Http\Controllers\LookupWordController;
use App\Modules\Vocabulary\Http\Controllers\RecordReviewsController;
use App\Modules\Vocabulary\Http\Controllers\SyncUserWordsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('vocabulary/sync', SyncUserWordsController::class);
    Route::post('vocabulary/reviews', RecordReviewsController::class);
    Route::post('vocabulary/lookup', LookupWordController::class)->middleware(['throttle:ai', 'quota:lookups']);
});
