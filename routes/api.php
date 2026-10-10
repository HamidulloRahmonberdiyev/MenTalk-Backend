<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\GoogleLoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\BootstrapController;
use App\Http\Controllers\Conversation\FinishConversationController;
use App\Http\Controllers\Conversation\HintController;
use App\Http\Controllers\Conversation\StartConversationController;
use App\Http\Controllers\Conversation\StoreTurnController;
use App\Http\Controllers\DeleteAccountController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\ShowProfileController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TranslateController;
use App\Http\Controllers\UpdateProfileController;
use App\Http\Controllers\Vocabulary\LookupWordController;
use App\Http\Controllers\Vocabulary\ReviewCardsController;
use App\Http\Controllers\Vocabulary\SyncCardsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('bootstrap', BootstrapController::class)->middleware('etag');
    Route::post('auth/google', GoogleLoginController::class)->middleware('throttle:auth');

    Route::middleware('auth:sanctum')->group(function () {
        Route::delete('auth/session', LogoutController::class);
        Route::get('me', ShowProfileController::class);
        Route::put('me', UpdateProfileController::class);
        Route::delete('me', DeleteAccountController::class);
        Route::get('history', HistoryController::class);
        Route::get('stats', StatsController::class);
        Route::post('vocabulary/sync', SyncCardsController::class);
        Route::post('vocabulary/reviews', ReviewCardsController::class);

        Route::middleware('throttle:ai')->group(function () {
            Route::post('conversations', StartConversationController::class)->middleware('quota:turns');
            Route::post('conversations/{conversation}/turns', StoreTurnController::class)->middleware('quota:turns');
            Route::post('conversations/{conversation}/hint', HintController::class);
            Route::post('conversations/{conversation}/finish', FinishConversationController::class);
            Route::post('translate', TranslateController::class)->middleware('quota:lookups');
            Route::post('vocabulary/lookup', LookupWordController::class)->middleware('quota:lookups');
        });
    });
});
