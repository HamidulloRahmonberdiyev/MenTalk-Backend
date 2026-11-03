<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The badge on the home screen: one cheap count instead of the whole list. */
final class UnreadCountController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json(['data' => ['unread_count' => self::count($request->user())]]);
    }

    public static function count(User $user): int
    {
        return $user->notifications()->reorder()->forInbox()->whereNull('read_at')->count();
    }
}
