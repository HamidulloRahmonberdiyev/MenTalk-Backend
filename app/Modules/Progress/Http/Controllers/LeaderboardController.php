<?php

declare(strict_types=1);

namespace App\Modules\Progress\Http\Controllers;

use App\Modules\Progress\Http\Requests\ShowLeaderboardRequest;
use App\Modules\Progress\Services\Leaderboard;
use App\Shared\Http\Controller;
use Illuminate\Http\JsonResponse;

final class LeaderboardController extends Controller
{
    public function __invoke(ShowLeaderboardRequest $request, Leaderboard $leaderboard): JsonResponse
    {
        return response()->json(['data' => $leaderboard->for($request->user(), $request->period(), $request->limit())]);
    }
}
