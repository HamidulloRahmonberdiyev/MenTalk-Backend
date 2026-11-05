<?php

declare(strict_types=1);

namespace App\Modules\Account\Http\Controllers;

use App\Modules\Account\Actions\ReplaceAvatar;
use App\Modules\Account\Http\Resources\UserResource;
use App\Shared\Http\Controller;
use Illuminate\Http\Request;

final class DeleteAvatarController extends Controller
{
    public function __invoke(Request $request, ReplaceAvatar $replace): UserResource
    {
        return new UserResource($replace($request->user(), null)->load('profile'));
    }
}
