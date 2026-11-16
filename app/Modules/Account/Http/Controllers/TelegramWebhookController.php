<?php

declare(strict_types=1);

namespace App\Modules\Account\Http\Controllers;

use App\Modules\Account\Actions\HandleTelegramUpdate;
use App\Shared\Http\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

/** Where Telegram delivers the sign-in bot's updates (registered by `php artisan telegram:setup`). */
final class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, HandleTelegramUpdate $handle): Response
    {
        $secret = (string) config('telegram.webhook_secret');

        abort_if($secret === '' || ! hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token')), 403);

        try {
            $handle($request->json()->all());
        } catch (Throwable $error) {
            // Telegram re-sends an update until it gets a 2xx; one broken update must not hold up the rest.
            report($error);
        }

        return response()->noContent();
    }
}
