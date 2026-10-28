<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Profile\ReplaceAvatar;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

final class DeleteAvatarController extends Controller
{
    public function __invoke(Request $request, ReplaceAvatar $replace): UserResource
    {
        return new UserResource($replace($request->user(), null)->load('profile'));
    }
}
