<?php

declare(strict_types=1);

namespace App\Modules\Account\Console\Commands;

use App\Modules\Account\Services\TelegramBot;
use App\Modules\Language\Enums\InterfaceLanguage;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'telegram:setup', description: 'Register the sign-in bot\'s webhook, commands and descriptions with Telegram')]
final class SetUpTelegram extends Command
{
    protected $signature = 'telegram:setup
        {--url= : Webhook URL (default: the telegram.webhook route under APP_URL)}
        {--local : Remove the webhook instead, so `telegram:poll` can fetch updates on this machine}';

    public function handle(TelegramBot $bot): int
    {
        if (! $bot->isConfigured()) {
            $this->components->error('TELEGRAM_BOT_TOKEN .env da yo‘q.');

            return self::FAILURE;
        }

        $me = $bot->call('getMe');
        $this->components->info("Bot: @{$me['username']} ({$me['first_name']})");

        if (strcasecmp((string) $me['username'], (string) config('telegram.bot_username')) !== 0) {
            $this->components->warn("TELEGRAM_BOT_USERNAME ({$this->botUsername()}) tokendagi botga mos emas: @{$me['username']}.");
        }

        $this->describe($bot);

        return $this->option('local') ? $this->removeWebhook($bot) : $this->registerWebhook($bot);
    }

    /** The texts people see before they ever write to the bot, in every app language. */
    private function describe(TelegramBot $bot): void
    {
        foreach ([null, ...InterfaceLanguage::cases()] as $language) {
            // Without a language code the texts are the default for every other language: Uzbek.
            $locale = $language?->value ?? InterfaceLanguage::Uzbek->value;
            $scope = array_filter(['language_code' => $language?->value]);

            $bot->call('setMyCommands', [
                'commands' => [['command' => 'start', 'description' => __('telegram.command_start', [], $locale)]],
                ...$scope,
            ]);
            $bot->call('setMyDescription', ['description' => __('telegram.description', [], $locale), ...$scope]);
            $bot->call('setMyShortDescription', ['short_description' => __('telegram.short_description', [], $locale), ...$scope]);
        }

        $this->components->info('Buyruqlar va tavsiflar o‘rnatildi (uz, ru, en).');
    }

    private function registerWebhook(TelegramBot $bot): int
    {
        $secret = (string) config('telegram.webhook_secret');
        $url = (string) ($this->option('url') ?: route('telegram.webhook'));

        if (preg_match('/^[A-Za-z0-9_-]{16,256}$/', $secret) !== 1) {
            $this->components->error('TELEGRAM_WEBHOOK_SECRET kamida 16 ta A-Z, a-z, 0-9, _ yoki - belgidan iborat bo‘lsin.');

            return self::FAILURE;
        }

        if (! str_starts_with($url, 'https://')) {
            $this->components->error("Webhook faqat HTTPS bo‘ladi: {$url}. APP_URL ni tekshiring yoki lokal uchun --local bilan ishga tushiring.");

            return self::FAILURE;
        }

        $bot->call('setWebhook', [
            'url' => $url,
            'secret_token' => $secret,
            // The bot speaks through messages only: the phone number arrives as one, and so does "cancel".
            'allowed_updates' => ['message'],
            'drop_pending_updates' => true,
            'max_connections' => 40,
        ]);

        $this->components->info("Webhook: {$url}");
        $this->reportWebhook($bot);

        return self::SUCCESS;
    }

    private function removeWebhook(TelegramBot $bot): int
    {
        $bot->call('deleteWebhook', ['drop_pending_updates' => true]);
        $this->components->info('Webhook o‘chirildi. Endi: php artisan telegram:poll');

        return self::SUCCESS;
    }

    private function reportWebhook(TelegramBot $bot): void
    {
        $info = $bot->call('getWebhookInfo');

        if (isset($info['last_error_message'])) {
            $this->components->warn("Telegram oxirgi xato: {$info['last_error_message']}");
        }

        $this->components->twoColumnDetail('Kutilayotgan yangilanishlar', (string) ($info['pending_update_count'] ?? 0));
    }

    private function botUsername(): string
    {
        return '@'.config('telegram.bot_username');
    }
}
