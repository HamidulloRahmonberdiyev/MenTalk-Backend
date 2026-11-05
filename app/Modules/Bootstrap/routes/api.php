<?php

declare(strict_types=1);

use App\Modules\Bootstrap\Http\Controllers\BootstrapController;
use Illuminate\Support\Facades\Route;

Route::get('bootstrap', BootstrapController::class)->middleware('etag');
