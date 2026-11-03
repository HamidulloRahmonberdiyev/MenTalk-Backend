<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

final class ListNotificationsController extends Controller
{
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'limit' => ['nullable', 'integer', 'between:1,50'],
            'lang' => ['nullable', Rule::in(['uz', 'ru', 'en'])],
        ]);

        /** @var User $user */
        $user = $request->user()->loadMissing('profile');
        $page = $user->notifications()->forInbox()->orderByDesc('id')->cursorPaginate($request->integer('limit', 30));

        return NotificationResource::collection($page)->additional(['meta' => [
            'unread_count' => UnreadCountController::count($user),
        ]]);
    }
}
