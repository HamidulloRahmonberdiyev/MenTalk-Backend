<?php

declare(strict_types=1);

namespace App\Http\Controllers\Conversation;

use App\Actions\Conversation\SendTurn;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTurnRequest;
use App\Http\Resources\TurnResource;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class StoreTurnController extends Controller
{
    public function __invoke(StoreTurnRequest $request, Conversation $conversation, SendTurn $send): JsonResponse
    {
        Gate::authorize('update', $conversation);

        return (new TurnResource($send($conversation, $request->toInput())))->response()->setStatusCode(200);
    }
}
