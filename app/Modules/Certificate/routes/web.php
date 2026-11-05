<?php

declare(strict_types=1);

use App\Modules\Certificate\Http\Controllers\VerifyCertificateController;
use Illuminate\Support\Facades\Route;

Route::get('verify/{serial}', VerifyCertificateController::class)->middleware('throttle:60,1')->name('certificates.verify');
