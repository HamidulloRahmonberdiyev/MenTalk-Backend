<?php

declare(strict_types=1);

namespace App\Modules\Progress\Http\Controllers;

use App\Modules\Progress\Services\DailyStats;
use App\Shared\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StatsController extends Controller
{
    public function __invoke(Request $request, DailyStats $stats): JsonResponse
    {
        return response()->json(['data' => $stats->for($request->user())]);
    }
}
