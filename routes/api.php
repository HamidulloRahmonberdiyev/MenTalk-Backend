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
use App\Http\Controllers\DeleteAvatarController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\Notifications\DeleteNotificationsController;
use App\Http\Controllers\Notifications\ListNotificationsController;
use App\Http\Controllers\Notifications\MarkNotificationsReadController;
use App\Http\Controllers\Notifications\UnreadCountController;
use App\Http\Controllers\ShowProfileController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TranslateController;
use App\Http\Controllers\UpdateAvatarController;
use App\Http\Controllers\UpdateProfileController;
use App\Http\Controllers\Vocabulary\LookupWordController;
use App\Http\Controllers\Vocabulary\RecordReviewsController;
use App\Http\Controllers\Vocabulary\SyncUserWordsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('bootstrap', BootstrapController::class)->middleware('etag');
    Route::post('auth/google', GoogleLoginController::class)->middleware('throttle:auth');

    Route::middleware('auth:sanctum')->group(function () {
        Route::delete('auth/session', LogoutController::class);
        Route::get('me', ShowProfileController::class);
        Route::put('me', UpdateProfileController::class);
        Route::delete('me', DeleteAccountController::class);
        Route::post('me/avatar', UpdateAvatarController::class)->middleware('throttle:10,1');
        Route::delete('me/avatar', DeleteAvatarController::class);
        Route::get('history', HistoryController::class);
        Route::get('stats', StatsController::class);
        Route::post('vocabulary/sync', SyncUserWordsController::class);
        Route::post('vocabulary/reviews', RecordReviewsController::class);

        Route::get('notifications', ListNotificationsController::class);
        Route::get('notifications/unread-count', UnreadCountController::class);
        Route::post('notifications/read', MarkNotificationsReadController::class);
        Route::post('notifications/delete', DeleteNotificationsController::class);

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
