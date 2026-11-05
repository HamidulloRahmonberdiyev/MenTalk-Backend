<?php

declare(strict_types=1);

namespace App\Modules\Account\Http\Controllers;

use App\Modules\Account\Actions\ReplaceAvatar;
use App\Modules\Account\Http\Requests\UpdateAvatarRequest;
use App\Modules\Account\Http\Resources\UserResource;
use App\Shared\Http\Controller;

final class UpdateAvatarController extends Controller
{
    public function __invoke(UpdateAvatarRequest $request, ReplaceAvatar $replace): UserResource
    {
        $user = $replace($request->user(), $request->file('photo'));

        return new UserResource($user->load('profile'));
    }
}
