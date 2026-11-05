<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Http\Controllers;

use App\Modules\Conversation\Actions\FinishConversation;
use App\Modules\Conversation\Http\Resources\ResultResource;
use App\Modules\Conversation\Models\Conversation;
use App\Shared\Http\Controller;
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
