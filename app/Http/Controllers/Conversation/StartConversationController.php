<?php

declare(strict_types=1);

namespace App\Http\Controllers\Conversation;

use App\Actions\Conversation\StartConversation;
use App\Http\Controllers\Controller;
use App\Http\Requests\StartConversationRequest;
use App\Http\Resources\TurnResource;
use Illuminate\Http\JsonResponse;

final class StartConversationController extends Controller
{
    public function __invoke(StartConversationRequest $request, StartConversation $start): JsonResponse
    {
        [$conversation, $turn] = $start($request->user(), $request->string('scenario_id')->toString());

        return response()->json([
            'data' => ['conversation_id' => $conversation->id] + (new TurnResource($turn))->resolve(),
        ], 201);
    }
}
