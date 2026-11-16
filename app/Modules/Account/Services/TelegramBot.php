<?php

declare(strict_types=1);

namespace App\Modules\Account\Services;

use App\Modules\Account\Exceptions\TelegramUnavailable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * A thin Bot API client for the sign-in bot.
 *
 * @see https://core.telegram.org/bots/api
 */
final readonly class TelegramBot
{
    private const API = 'https://api.telegram.org';

    public function __construct(private ?string $token) {}

    public function isConfigured(): bool
    {
        return $this->token !== null && $this->token !== '';
    }

    /**
     * Calls a Bot API method and returns its `result`.
     *
     * @param  array<string, mixed>  $params
     *
     * @throws TelegramUnavailable
     */
    public function call(string $method, array $params = [], int $timeout = 10): mixed
    {
        if (! $this->isConfigured()) {
            throw new TelegramUnavailable('TELEGRAM_BOT_TOKEN is not set');
        }

        try {
            $response = Http::acceptJson()->asJson()->connectTimeout(5)->timeout($timeout)
                ->post(self::API."/bot{$this->token}/{$method}", $params);
        } catch (ConnectionException) {
            // The exception message includes the URL, and with it the token: never let it reach a log.
            throw new TelegramUnavailable("Telegram {$method}: connection failed");
        }

        if ($response->json('ok') !== true) {
            $code = (int) $response->json('error_code', $response->status());

            throw new TelegramUnavailable("Telegram {$method}: {$code} ".$response->json('description', 'unknown error'), $code);
        }

        return $response->json('result');
    }

    /** @param  array<string, mixed>  $options */
    public function sendMessage(int $chatId, string $html, array $options = []): array
    {
        return $this->call('sendMessage', [
            'chat_id' => $chatId,
            'text' => $html,
            'parse_mode' => 'HTML',
            'link_preview_options' => ['is_disabled' => true],
            ...$options,
        ]);
    }

    /** @param  array<string, mixed>  $options */
    public function editMessageText(int $chatId, int $messageId, string $html, array $options = []): void
    {
        $this->call('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $html,
            'parse_mode' => 'HTML',
            'link_preview_options' => ['is_disabled' => true],
            ...$options,
        ]);
    }

    public function answerCallbackQuery(string $id, ?string $text = null, bool $alert = false): void
    {
        $this->call('answerCallbackQuery', array_filter(['callback_query_id' => $id, 'text' => $text, 'show_alert' => $alert ?: null]));
    }

    /** The bytes of a file from `getFile`. */
    public function download(string $filePath): string
    {
        try {
            $response = Http::connectTimeout(5)->timeout(15)->get(self::API."/file/bot{$this->token}/{$filePath}");
        } catch (ConnectionException) {
            throw new TelegramUnavailable('Telegram file download: connection failed');
        }

        if (! $response->successful()) {
            throw new TelegramUnavailable('Telegram file download: '.$response->status(), $response->status());
        }

        return $response->body();
    }
}
