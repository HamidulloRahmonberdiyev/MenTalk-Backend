<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vocabulary;

use App\Actions\Vocabulary\SyncCards;
use App\Http\Controllers\Controller;
use App\Http\Requests\SyncCardsRequest;
use App\Http\Resources\CardResource;
use Illuminate\Http\JsonResponse;

final class SyncCardsController extends Controller
{
    public function __invoke(SyncCardsRequest $request, SyncCards $sync): JsonResponse
    {
        $result = $sync($request->user(), $request->cursor(), $request->changes());

        return response()->json(['data' => [
            'cards' => CardResource::collection($result->cards)->resolve(),
            'cursor' => $result->cursor->toIso8601String(),
        ]]);
    }
}
