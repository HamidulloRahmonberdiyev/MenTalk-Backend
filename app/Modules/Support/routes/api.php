<?php

declare(strict_types=1);

use App\Modules\Support\Http\Controllers\SupportController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('support', [SupportController::class, 'links']);
    Route::get('support/tickets', [SupportController::class, 'index']);
    Route::post('support/tickets', [SupportController::class, 'store'])->middleware('throttle:support');
});
