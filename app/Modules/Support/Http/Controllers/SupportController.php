<?php

declare(strict_types=1);

namespace App\Modules\Support\Http\Controllers;

use App\Modules\Support\Http\Requests\StoreTicketRequest;
use App\Modules\Support\Models\SupportTicket;
use App\Shared\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SupportController extends Controller
{
    /** Where else the learner can find the team; links the admin can change without an app release. */
    public function links(): JsonResponse
    {
        return response()->json(['data' => ['telegram_url' => config('support.telegram_url') ?: null]]);
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        $ticket = SupportTicket::query()->create([...$request->validated(), 'user_id' => $request->user()->id]);

        return response()->json(['data' => ['id' => $ticket->id, 'status' => $ticket->status->value]], 201);
    }

    /** The learner's own tickets, newest first, so they can see one was received and what became of it. */
    public function index(Request $request): JsonResponse
    {
        $tickets = SupportTicket::query()
            ->where('user_id', $request->user()->id)
            ->latest('id')->limit(20)
            ->get(['id', 'category', 'message', 'status', 'created_at']);

        return response()->json(['data' => $tickets->map(fn (SupportTicket $ticket) => [
            'id' => $ticket->id,
            'category' => $ticket->category->value,
            'message' => $ticket->message,
            'status' => $ticket->status->value,
            'created_at' => $ticket->created_at?->toIso8601String(),
        ])]);
    }
}
