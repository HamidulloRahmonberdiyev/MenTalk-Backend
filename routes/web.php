<?php

use App\Http\Controllers\Certificate\VerifyCertificateController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('verify/{serial}', VerifyCertificateController::class)->middleware('throttle:60,1')->name('certificates.verify');
