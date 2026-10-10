<?php

declare(strict_types=1);

namespace App\Http\Controllers\Conversation;

use App\Actions\Conversation\RequestHint;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class HintController extends Controller
{
    public function __invoke(Conversation $conversation, RequestHint $hint): JsonResponse
    {
        Gate::authorize('update', $conversation);

        return response()->json(['data' => ['hint' => $hint($conversation)]]);
    }
}
