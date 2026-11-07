<?php

declare(strict_types=1);

use App\Modules\Mistakes\Http\Controllers\MistakesController;
use App\Modules\Mistakes\Http\Controllers\RecordMistakeReviewsController;
use Illuminate\Support\Facades\Route;

// No AI anywhere here: mistakes are collected from conversation turns, and the drills are built
// on the device from the stored correction.
Route::middleware('auth:sanctum')->prefix('mistakes')->group(function () {
    Route::get('/', [MistakesController::class, 'index']);
    Route::get('summary', [MistakesController::class, 'summary']);
    Route::get('practice', [MistakesController::class, 'practice']);
    Route::post('reviews', RecordMistakeReviewsController::class)->middleware('throttle:30,1');
    Route::delete('{id}', [MistakesController::class, 'dismiss'])->whereUlid('id');
    Route::post('{id}/restore', [MistakesController::class, 'restore'])->whereUlid('id');
});
