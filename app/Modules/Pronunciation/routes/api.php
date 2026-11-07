<?php

declare(strict_types=1);

use App\Modules\Pronunciation\Http\Controllers\PhrasesController;
use Illuminate\Support\Facades\Route;

// POST, not GET: handing lines out marks them seen. The AI quota (`phrases`) is charged inside,
// only when a new batch is actually written.
Route::post('pronunciation/phrases/next', PhrasesController::class)->middleware(['auth:sanctum', 'throttle:30,1']);
