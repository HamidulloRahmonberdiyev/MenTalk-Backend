<?php

declare(strict_types=1);

use App\Modules\Notification\Http\Controllers\DeleteNotificationsController;
use App\Modules\Notification\Http\Controllers\ListNotificationsController;
use App\Modules\Notification\Http\Controllers\MarkNotificationsReadController;
use App\Modules\Notification\Http\Controllers\RegisterPushTokenController;
use App\Modules\Notification\Http\Controllers\UnreadCountController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('notifications', ListNotificationsController::class);
    Route::get('notifications/unread-count', UnreadCountController::class);
    Route::post('notifications/read', MarkNotificationsReadController::class);
    Route::post('notifications/delete', DeleteNotificationsController::class);
    Route::post('me/push-token', RegisterPushTokenController::class)->middleware('throttle:30,1');
});
