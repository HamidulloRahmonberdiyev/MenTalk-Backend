<?php

declare(strict_types=1);

use App\Modules\Account\Http\Controllers\DeleteAccountController;
use App\Modules\Account\Http\Controllers\DeleteAvatarController;
use App\Modules\Account\Http\Controllers\GoogleLoginController;
use App\Modules\Account\Http\Controllers\LogoutController;
use App\Modules\Account\Http\Controllers\ShowProfileController;
use App\Modules\Account\Http\Controllers\UpdateAvatarController;
use App\Modules\Account\Http\Controllers\UpdateProfileController;
use Illuminate\Support\Facades\Route;

Route::post('auth/google', GoogleLoginController::class)->middleware('throttle:auth');

Route::middleware('auth:sanctum')->group(function () {
    Route::delete('auth/session', LogoutController::class);
    Route::get('me', ShowProfileController::class);
    Route::put('me', UpdateProfileController::class);
    Route::delete('me', DeleteAccountController::class);
    Route::post('me/avatar', UpdateAvatarController::class)->middleware('throttle:10,1');
    Route::delete('me/avatar', DeleteAvatarController::class);
});
