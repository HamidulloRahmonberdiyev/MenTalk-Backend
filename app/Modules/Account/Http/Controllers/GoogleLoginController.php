<?php

declare(strict_types=1);

namespace App\Modules\Account\Http\Controllers;

use App\Modules\Account\Actions\LoginWithGoogle;
use App\Modules\Account\Http\Requests\GoogleLoginRequest;
use App\Modules\Account\Http\Resources\UserResource;
use App\Modules\Language\Enums\InterfaceLanguage;
use App\Shared\Http\Controller;
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
