<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ConversationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class HistoryController extends Controller
{
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        return ConversationResource::collection(
            $request->user()->conversations()->latest('started_at')->latest('id')->cursorPaginate(20),
        );
    }
}
