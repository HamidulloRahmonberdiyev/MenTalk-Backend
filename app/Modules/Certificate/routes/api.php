<?php

declare(strict_types=1);

use App\Modules\Certificate\Http\Controllers\CertificateDocumentController;
use App\Modules\Certificate\Http\Controllers\IssueCertificateController;
use App\Modules\Certificate\Http\Controllers\ShowCertificateController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('certificate', ShowCertificateController::class);
    Route::post('certificate', IssueCertificateController::class)->middleware('throttle:10,1');
    Route::get('certificate/{serial}/document', CertificateDocumentController::class)->middleware('throttle:10,1');
});
