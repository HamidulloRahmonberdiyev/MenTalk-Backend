<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Http\Controllers;

use App\Modules\Conversation\Actions\SendTurn;
use App\Modules\Conversation\Http\Requests\StoreTurnRequest;
use App\Modules\Conversation\Http\Resources\TurnResource;
use App\Modules\Conversation\Models\Conversation;
use App\Shared\Http\Controller;
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
