<?php

declare(strict_types=1);

return [
    // From @BotFather. Without it, Telegram sign-in answers 503 and the bot stays silent.
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),

    'bot_username' => env('TELEGRAM_BOT_USERNAME', 'mentalk_auth_bot'),

    // Telegram echoes it in X-Telegram-Bot-Api-Secret-Token, so only Telegram can call the webhook.
    // 1–256 characters of A–Z, a–z, 0–9, _ and -. `php artisan telegram:setup` registers it.
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),

    // How long a sign-in link waits for the tap in Telegram, and then for the app to collect the session.
    'login_ttl_seconds' => (int) env('TELEGRAM_LOGIN_TTL', 300),

    // Where the bot's "back to the app" button leads.
    'app_scheme' => env('MOBILE_APP_SCHEME', 'govori'),
    'android_package' => env('MOBILE_ANDROID_PACKAGE', 'com.hamidullo_rahmonberdiyev.govori'),
];
