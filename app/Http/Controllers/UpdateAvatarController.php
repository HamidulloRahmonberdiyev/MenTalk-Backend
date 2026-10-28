<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Profile\ReplaceAvatar;
use App\Http\Requests\UpdateAvatarRequest;
use App\Http\Resources\UserResource;

final class UpdateAvatarController extends Controller
{
    public function __invoke(UpdateAvatarRequest $request, ReplaceAvatar $replace): UserResource
    {
        $user = $replace($request->user(), $request->file('photo'));

        return new UserResource($user->load('profile'));
    }
}
