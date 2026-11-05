<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Http\Controllers;

use App\Modules\Conversation\Actions\RequestHint;
use App\Modules\Conversation\Models\Conversation;
use App\Shared\Http\Controller;
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
