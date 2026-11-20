<?php

declare(strict_types=1);

use App\Modules\Conversation\Http\Controllers\FinishConversationController;
use App\Modules\Conversation\Http\Controllers\HintController;
use App\Modules\Conversation\Http\Controllers\HistoryController;
use App\Modules\Conversation\Http\Controllers\PathController;
use App\Modules\Conversation\Http\Controllers\StartConversationController;
use App\Modules\Conversation\Http\Controllers\StoreTurnController;
use App\Modules\Conversation\Http\Controllers\StreamTurnController;
use App\Modules\Conversation\Http\Controllers\TurnSpeechController;
use App\Modules\Conversation\Http\Controllers\TutorVoiceSampleController;
use App\Modules\Conversation\Http\Controllers\TutorVoicesController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('history', HistoryController::class);
    Route::get('path', PathController::class);
    Route::get('tutor-voices', TutorVoicesController::class);

    Route::middleware('throttle:ai')->group(function () {
        Route::post('tutor-voices/{voice}/sample', TutorVoiceSampleController::class);
        Route::post('conversations', StartConversationController::class)->middleware('quota:turns');
        Route::post('conversations/{conversation}/turns', StoreTurnController::class)->middleware('quota:turns');
        // Charges the daily allowance itself: a retried turn or the deferred opening costs nothing.
        Route::post('conversations/{conversation}/turns/stream', StreamTurnController::class);
        Route::post('conversations/{conversation}/turns/{turn}/speech', TurnSpeechController::class)->scopeBindings();
        Route::post('conversations/{conversation}/hint', HintController::class);
        Route::post('conversations/{conversation}/finish', FinishConversationController::class);
    });
});
