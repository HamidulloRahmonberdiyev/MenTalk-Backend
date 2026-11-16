<?php

declare(strict_types=1);

namespace App\Modules\Account\Http\Controllers;

use App\Modules\Account\Actions\StartTelegramLogin;
use App\Modules\Account\Http\Requests\StartTelegramLoginRequest;
use App\Modules\Language\Enums\InterfaceLanguage;
use App\Shared\Http\Controller;
use Illuminate\Http\JsonResponse;

final class StartTelegramLoginController extends Controller
{
    /** How often the app should ask whether the person has confirmed in Telegram. */
    private const POLL_INTERVAL_MS = 2000;

    public function __invoke(StartTelegramLoginRequest $request, StartTelegramLogin $start): JsonResponse
    {
        ['login' => $login, 'code' => $code, 'secret' => $secret] = $start(
            $request->string('device')->toString(),
            $request->enum('ui_language', InterfaceLanguage::class),
        );

        $bot = (string) config('telegram.bot_username');

        return response()->json([
            'data' => [
                'id' => $login->id,
                'secret' => $secret,
                'bot_username' => $bot,
                // Opens Telegram straight away when it is installed …
                'app_url' => "tg://resolve?domain={$bot}&start={$code}",
                // … and otherwise the web page that offers to install it.
                'bot_url' => "https://t.me/{$bot}?start={$code}",
                'expires_at' => $login->expires_at->toIso8601ZuluString(),
                'poll_interval_ms' => self::POLL_INTERVAL_MS,
            ],
        ], 201);
    }
}
