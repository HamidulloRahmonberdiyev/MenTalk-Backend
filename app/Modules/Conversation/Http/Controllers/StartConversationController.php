<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Http\Controllers;

use App\Modules\Conversation\Actions\StartConversation;
use App\Modules\Conversation\Http\Requests\StartConversationRequest;
use App\Modules\Conversation\Http\Resources\TurnResource;
use App\Shared\Http\Controller;
use Illuminate\Http\JsonResponse;

final class StartConversationController extends Controller
{
    public function __invoke(StartConversationRequest $request, StartConversation $start): JsonResponse
    {
        [$conversation, $turn] = $start(
            $request->user(),
            $request->string('scenario_id')->toString(),
            $request->targetLanguage(),
        );

        return response()->json([
            'data' => [
                'conversation_id' => $conversation->id,
                'target_language' => $conversation->target_language,
            ] + (new TurnResource($turn))->resolve(),
        ], 201);
    }
}
