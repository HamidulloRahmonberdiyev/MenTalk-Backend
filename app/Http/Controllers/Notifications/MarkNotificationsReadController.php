<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationSelectionRequest;
use Illuminate\Http\JsonResponse;

/** Idempotent: unknown, foreign or already read ids are simply skipped, so the app can resend. */
final class MarkNotificationsReadController extends Controller
{
    public function __invoke(NotificationSelectionRequest $request): JsonResponse
    {
        $request->selection()->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['data' => ['unread_count' => UnreadCountController::count($request->user())]]);
    }
}
