<?php

declare(strict_types=1);

use App\Modules\Conversation\Http\Controllers\FinishConversationController;
use App\Modules\Conversation\Http\Controllers\HintController;
use App\Modules\Conversation\Http\Controllers\HistoryController;
use App\Modules\Conversation\Http\Controllers\PathController;
use App\Modules\Conversation\Http\Controllers\StartConversationController;
use App\Modules\Conversation\Http\Controllers\StoreTurnController;
use App\Modules\Conversation\Http\Controllers\StreamTurnController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('history', HistoryController::class);
    Route::get('path', PathController::class);

    Route::middleware('throttle:ai')->group(function () {
        Route::post('conversations', StartConversationController::class)->middleware('quota:turns');
        Route::post('conversations/{conversation}/turns', StoreTurnController::class)->middleware('quota:turns');
        Route::post('conversations/{conversation}/turns/stream', StreamTurnController::class)->middleware('quota:turns');
        Route::post('conversations/{conversation}/hint', HintController::class);
        Route::post('conversations/{conversation}/finish', FinishConversationController::class);
    });
});
