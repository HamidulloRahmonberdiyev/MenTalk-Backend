<?php

declare(strict_types=1);

use App\Modules\Account\Http\Controllers\TelegramReturnController;
use Illuminate\Support\Facades\Route;

Route::get('telegram/return', TelegramReturnController::class)->middleware('throttle:60,1')->name('telegram.return');
