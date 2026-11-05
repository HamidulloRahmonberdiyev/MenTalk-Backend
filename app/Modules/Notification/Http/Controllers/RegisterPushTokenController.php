<?php

declare(strict_types=1);

namespace App\Modules\Notification\Http\Controllers;

use App\Modules\Notification\Http\Requests\RegisterPushTokenRequest;
use App\Modules\Notification\Models\PushToken;
use App\Shared\Http\Controller;
use Illuminate\Http\Response;

final class RegisterPushTokenController extends Controller
{
    public function __invoke(RegisterPushTokenRequest $request): Response
    {
        $user = $request->user();

        PushToken::query()->updateOrCreate(['token' => $request->string('token')->value()], [
            'user_id' => $user->id,
            'personal_access_token_id' => $user->currentAccessToken()->getKey(),
            'platform' => $request->string('platform')->value(),
        ]);

        return response()->noContent();
    }
}
