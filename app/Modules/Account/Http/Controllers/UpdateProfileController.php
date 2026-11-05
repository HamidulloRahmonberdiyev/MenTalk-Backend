<?php

declare(strict_types=1);

namespace App\Modules\Account\Http\Controllers;

use App\Modules\Account\Actions\UpdateProfile;
use App\Modules\Account\Http\Requests\UpdateProfileRequest;
use App\Modules\Account\Http\Resources\UserResource;
use App\Shared\Http\Controller;

final class UpdateProfileController extends Controller
{
    public function __invoke(UpdateProfileRequest $request, UpdateProfile $update): UserResource
    {
        return new UserResource($update($request->user(), $request->validated()));
    }
}
