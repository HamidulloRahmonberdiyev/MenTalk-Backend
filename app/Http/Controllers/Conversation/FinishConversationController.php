<?php

declare(strict_types=1);

namespace App\Http\Controllers\Conversation;

use App\Actions\Conversation\FinishConversation;
use App\Http\Controllers\Controller;
use App\Http\Resources\ResultResource;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class FinishConversationController extends Controller
{
    public function __invoke(Conversation $conversation, FinishConversation $finish): JsonResponse
    {
        Gate::authorize('view', $conversation);

        return (new ResultResource($finish($conversation)))->response()->setStatusCode(200);
    }
}
