<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Profile\UpdateProfile;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;

final class UpdateProfileController extends Controller
{
    public function __invoke(UpdateProfileRequest $request, UpdateProfile $update): UserResource
    {
        return new UserResource($update($request->user(), $request->validated()));
    }
}
