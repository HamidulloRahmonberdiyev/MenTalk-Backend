<?php

declare(strict_types=1);

namespace App\Modules\Account\Http\Controllers;

use App\Modules\Account\Http\Resources\UserResource;
use App\Shared\Http\Controller;
use Illuminate\Http\Request;

final class ShowProfileController extends Controller
{
    public function __invoke(Request $request): UserResource
    {
        return new UserResource($request->user()->load('profile'));
    }
}
