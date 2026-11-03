<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationSelectionRequest;
use Illuminate\Http\JsonResponse;

/** Idempotent like marking read: deleting what is already gone is not an error. */
final class DeleteNotificationsController extends Controller
{
    public function __invoke(NotificationSelectionRequest $request): JsonResponse
    {
        $request->selection()->delete();

        return response()->json(['data' => ['unread_count' => UnreadCountController::count($request->user())]]);
    }
}
