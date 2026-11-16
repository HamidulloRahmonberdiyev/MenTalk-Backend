<?php

declare(strict_types=1);

namespace App\Modules\Account\Http\Controllers;

use App\Modules\Language\Enums\InterfaceLanguage;
use App\Shared\Http\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Behind the bot's "back to MenTalk" button: Telegram buttons only take http(s) links, so this page
 * hands over to the app's own scheme (as an intent on Android, which Chrome-based browsers open reliably).
 */
final class TelegramReturnController extends Controller
{
    private const PATH = 'telegram-return';

    public function __invoke(Request $request): View
    {
        $language = InterfaceLanguage::tryFrom($request->string('lang')->toString()) ?? InterfaceLanguage::Uzbek;
        $scheme = (string) config('telegram.app_scheme');
        $package = (string) config('telegram.android_package');
        $isAndroid = str_contains((string) $request->userAgent(), 'Android');

        return view('telegram.return', [
            'locale' => $language->value,
            'appUrl' => $isAndroid
                ? 'intent://'.self::PATH."#Intent;scheme={$scheme};package={$package};end"
                : "{$scheme}://".self::PATH,
        ]);
    }
}
