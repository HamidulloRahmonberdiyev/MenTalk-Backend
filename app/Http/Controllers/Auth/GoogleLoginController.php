<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\LoginWithGoogle;
use App\Enums\InterfaceLanguage;
use App\Http\Controllers\Controller;
use App\Http\Requests\GoogleLoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

final class GoogleLoginController extends Controller
{
    public function __invoke(GoogleLoginRequest $request, LoginWithGoogle $login): JsonResponse
    {
        [$user, $token] = $login(
            $request->string('id_token')->toString(),
            $request->string('device')->toString(),
            $request->enum('ui_language', InterfaceLanguage::class),
        );

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => (new UserResource($user->load('profile')))->resolve(),
            ],
        ]);
    }
}
