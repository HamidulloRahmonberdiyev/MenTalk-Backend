<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;

final class UpdateProfileController extends Controller
{
    public function __invoke(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();
        $data = $request->validated();

        if (isset($data['name'])) {
            $user->update(['name' => $data['name']]);
        }

        $user->profile()->firstOrCreate([])->update(collect($data)->except('name')->all());

        return new UserResource($user->load('profile'));
    }
}
