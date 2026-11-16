<?php

declare(strict_types=1);

namespace App\Modules\Account\Actions;

use App\Modules\Account\Enums\TelegramLoginStatus;
use App\Modules\Account\Exceptions\TelegramUnavailable;
use App\Modules\Account\Models\TelegramLogin;
use App\Modules\Account\Services\TelegramBot;
use App\Modules\Language\Enums\InterfaceLanguage;
use Illuminate\Support\Str;

final readonly class StartTelegramLogin
{
    public function __construct(private TelegramBot $bot) {}

    /**
     * Opens a sign-in attempt. The code goes into the bot's deep link; the secret is what the app polls with.
     *
     * @return array{login: TelegramLogin, code: string, secret: string}
     */
    public function __invoke(string $device, ?InterfaceLanguage $language): array
    {
        if (! $this->bot->isConfigured()) {
            throw new TelegramUnavailable('TELEGRAM_BOT_TOKEN is not set');
        }

        // A deep-link payload allows 64 characters of [A-Za-z0-9_-]; 40 alphanumerics are ~238 bits.
        $code = Str::random(40);
        $secret = Str::random(64);

        $login = TelegramLogin::query()->create([
            'code_hash' => TelegramLogin::hash($code),
            'secret_hash' => TelegramLogin::hash($secret),
            'status' => TelegramLoginStatus::Pending,
            'device' => Str::limit($device, 120, ''),
            'language' => $language,
            'expires_at' => now()->addSeconds((int) config('telegram.login_ttl_seconds')),
        ]);

        return ['login' => $login, 'code' => $code, 'secret' => $secret];
    }
}
