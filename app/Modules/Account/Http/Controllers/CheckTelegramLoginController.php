<?php

declare(strict_types=1);

namespace App\Modules\Account\Http\Controllers;

use App\Modules\Account\Actions\CheckTelegramLogin;
use App\Modules\Account\Http\Requests\CheckTelegramLoginRequest;
use App\Modules\Account\Http\Resources\UserResource;
use App\Modules\Account\Models\TelegramLogin;
use App\Shared\Http\Controller;
use Illuminate\Http\JsonResponse;

final class CheckTelegramLoginController extends Controller
{
    public function __invoke(CheckTelegramLoginRequest $request, TelegramLogin $telegramLogin, CheckTelegramLogin $check): JsonResponse
    {
        // Without the secret the attempt does not exist, for anyone who only saw its id or its code.
        abort_unless($telegramLogin->hasSecret($request->string('secret')->toString()), 404);

        $result = $check($telegramLogin);

        if ($result['status'] !== 'approved') {
            return response()->json(['data' => ['status' => $result['status']]]);
        }

        return response()->json([
            'data' => [
                'status' => 'approved',
                'token' => $result['token'],
                'user' => (new UserResource($result['user']->load('profile')))->resolve(),
            ],
        ]);
    }
}
