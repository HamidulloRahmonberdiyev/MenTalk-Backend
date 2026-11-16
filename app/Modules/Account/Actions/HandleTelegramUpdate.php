<?php

declare(strict_types=1);

namespace App\Modules\Account\Actions;

use App\Modules\Account\DTO\TelegramUser;
use App\Modules\Account\Enums\TelegramLoginStatus;
use App\Modules\Account\Exceptions\AccountBlocked;
use App\Modules\Account\Models\TelegramLogin;
use App\Modules\Account\Services\TelegramBot;
use App\Modules\Language\Enums\InterfaceLanguage;
use Illuminate\Support\Facades\DB;

/**
 * The sign-in bot's conversation. The app's deep link arrives as `/start <code>`; the bot answers with
 * the device asking to sign in and two buttons. Only "confirm", pressed by the same Telegram user,
 * ties the attempt to an account: a link someone else sent cannot sign their phone into yours.
 *
 * @see https://core.telegram.org/bots/features#deep-linking
 */
final readonly class HandleTelegramUpdate
{
    private const START = '/^\/start(?:@\w+)?(?:\s+([A-Za-z0-9_-]{1,64}))?$/';

    private const BUTTON = '/^login:(approve|decline):([0-9A-HJKMNP-TV-Za-hjkmnp-tv-z]{26})$/';

    public function __construct(private TelegramBot $bot, private ResolveTelegramAccount $resolveAccount) {}

    /** @param  array<string, mixed>  $update */
    public function __invoke(array $update): void
    {
        if (is_array($update['callback_query'] ?? null)) {
            $this->onButton($update['callback_query']);

            return;
        }

        $message = $update['message'] ?? null;

        // Groups and channels are not for signing in; neither are other bots.
        if (! is_array($message) || ($message['chat']['type'] ?? null) !== 'private' || ! is_array($message['from'] ?? null)) {
            return;
        }

        $from = TelegramUser::fromUpdate($message['from']);
        $chatId = (int) $message['chat']['id'];

        if ($from->isBot) {
            return;
        }

        if (preg_match(self::START, trim((string) ($message['text'] ?? '')), $match) !== 1) {
            $this->bot->sendMessage($chatId, $this->text('help', $from->interfaceLanguage()));

            return;
        }

        if (! isset($match[1])) {
            $this->bot->sendMessage($chatId, $this->text('welcome', $from->interfaceLanguage(), ['name' => e($from->firstName)]));

            return;
        }

        $this->onStart($chatId, $from, $match[1]);
    }

    private function onStart(int $chatId, TelegramUser $from, string $code): void
    {
        $login = TelegramLogin::findByCode($code);
        $language = $login?->language ?? $from->interfaceLanguage();

        if ($login === null || ! $login->isOpen()) {
            $this->bot->sendMessage($chatId, $this->text('expired', $language));

            return;
        }

        if ($login->telegram_user_id !== null && $login->telegram_user_id !== $from->id) {
            $this->bot->sendMessage($chatId, $this->text('taken', $language));

            return;
        }

        $sent = $this->bot->sendMessage($chatId, $this->text('prompt', $language, [
            'device' => e($login->device),
            'minutes' => max(1, (int) ceil(now()->diffInSeconds($login->expires_at) / 60)),
        ]), [
            'reply_markup' => ['inline_keyboard' => [[
                ['text' => $this->text('approve', $language), 'callback_data' => "login:approve:{$login->id}"],
                ['text' => $this->text('decline', $language), 'callback_data' => "login:decline:{$login->id}"],
            ]]],
        ]);

        // Claimed only if nobody else got there first.
        TelegramLogin::query()
            ->whereKey($login->id)
            ->where(fn ($query) => $query->whereNull('telegram_user_id')->orWhere('telegram_user_id', $from->id))
            ->update([
                'status' => TelegramLoginStatus::Opened,
                'telegram_user_id' => $from->id,
                'chat_id' => $chatId,
                'message_id' => $sent['message_id'],
            ]);
    }

    /** @param  array<string, mixed>  $query */
    private function onButton(array $query): void
    {
        $from = TelegramUser::fromUpdate($query['from']);

        if (preg_match(self::BUTTON, (string) ($query['data'] ?? ''), $match) !== 1) {
            $this->bot->answerCallbackQuery((string) $query['id']);

            return;
        }

        [, $action, $loginId] = $match;

        [$outcome, $language] = DB::transaction(function () use ($action, $loginId, $from): array {
            $login = TelegramLogin::query()->lockForUpdate()->find($loginId);

            if ($login === null || $login->telegram_user_id !== $from->id) {
                return ['expired', $from->interfaceLanguage()];
            }

            $language = $login->language ?? $from->interfaceLanguage();

            if (! $login->isOpen()) {
                return [match ($login->status) {
                    TelegramLoginStatus::Approved, TelegramLoginStatus::Completed => 'approved',
                    TelegramLoginStatus::Declined => 'declined',
                    default => 'expired',
                }, $language];
            }

            if ($action === 'decline') {
                $login->update(['status' => TelegramLoginStatus::Declined]);

                return ['declined', $language];
            }

            try {
                $user = ($this->resolveAccount)($from, $login->language);
            } catch (AccountBlocked) {
                $login->update(['status' => TelegramLoginStatus::Declined]);

                return ['blocked', $language];
            }

            // The app now has a fresh window to collect its session.
            $login->update([
                'status' => TelegramLoginStatus::Approved,
                'user_id' => $user->id,
                'approved_at' => now(),
                'expires_at' => now()->addSeconds((int) config('telegram.login_ttl_seconds')),
            ]);

            return ['approved', $language];
        });

        $this->bot->answerCallbackQuery((string) $query['id'], $this->text("{$outcome}_toast", $language), alert: $outcome === 'expired');

        $message = $query['message'] ?? null;

        // A message older than 48 hours arrives as "inaccessible" (date 0) and cannot be edited.
        if (! is_array($message) || ($message['date'] ?? 0) === 0) {
            return;
        }

        // Cosmetic: the outcome already stands even if Telegram refuses the edit.
        rescue(fn () => $this->bot->editMessageText(
            (int) $message['chat']['id'],
            (int) $message['message_id'],
            $this->text($outcome, $language, ['support' => e((string) config('support.telegram_url'))]),
            $outcome === 'approved' ? $this->returnButton($language) : [],
        ));
    }

    /** "Back to MenTalk", through a page that opens the app: Telegram buttons accept only http(s) links. */
    private function returnButton(InterfaceLanguage $language): array
    {
        $url = route('telegram.return', ['lang' => $language->value]);

        if (! str_starts_with($url, 'https://')) {
            return [];
        }

        return ['reply_markup' => ['inline_keyboard' => [[['text' => $this->text('return_button', $language), 'url' => $url]]]]];
    }

    /** @param  array<string, string|int>  $replace */
    private function text(string $key, InterfaceLanguage $language, array $replace = []): string
    {
        return (string) __("telegram.{$key}", $replace, $language->value);
    }
}
