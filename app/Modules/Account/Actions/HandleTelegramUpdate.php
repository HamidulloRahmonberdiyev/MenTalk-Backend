<?php

declare(strict_types=1);

namespace App\Modules\Account\Actions;

use App\Modules\Account\DTO\TelegramContact;
use App\Modules\Account\DTO\TelegramUser;
use App\Modules\Account\Enums\TelegramLoginStatus;
use App\Modules\Account\Exceptions\AccountBlocked;
use App\Modules\Account\Models\TelegramLogin;
use App\Modules\Account\Services\TelegramBot;
use App\Modules\Language\Enums\InterfaceLanguage;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * The sign-in bot's conversation. The app's deep link arrives as `/start <code>`; the bot answers with
 * the device asking to sign in and a keyboard whose button shares the person's phone number. Only that
 * number, sent by the Telegram user who opened the link, ties the attempt to an account: a link someone
 * else sent cannot sign their phone into yours, and a contact card from the address book is refused.
 *
 * @see https://core.telegram.org/bots/features#deep-linking
 * @see https://core.telegram.org/bots/api#keyboardbutton
 */
final readonly class HandleTelegramUpdate
{
    private const START = '/^\/start(?:@\w+)?(?:\s+([A-Za-z0-9_-]{1,64}))?$/';

    /** Goes with every final answer, so the phone button never outlives the request it belongs to. */
    private const REMOVE_KEYBOARD = ['reply_markup' => ['remove_keyboard' => true]];

    public function __construct(private TelegramBot $bot, private ResolveTelegramAccount $resolveAccount) {}

    /** @param  array<string, mixed>  $update */
    public function __invoke(array $update): void
    {
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

        $text = trim((string) ($message['text'] ?? ''));
        $contact = is_array($message['contact'] ?? null) ? $message['contact'] : null;

        if (preg_match(self::START, $text, $match) === 1) {
            isset($match[1])
                ? $this->onStart($chatId, $from, $match[1])
                : $this->bot->sendMessage($chatId, $this->text('welcome', $from->interfaceLanguage(), ['name' => e($from->firstName)]));

            return;
        }

        $login = TelegramLogin::answerableBy($from->id)->first();

        if ($login === null) {
            // Also clears a phone button left over from a request that has since expired.
            $this->bot->sendMessage($chatId, $this->text($contact !== null ? 'expired' : 'help', $from->interfaceLanguage()), self::REMOVE_KEYBOARD);

            return;
        }

        $language = $login->language ?? $from->interfaceLanguage();

        if ($contact !== null) {
            $this->onContact($chatId, $from, $login, $language, $contact);
        } elseif ($this->isCancel($text)) {
            $this->onCancel($chatId, $login, $language);
        } else {
            // A number typed by hand proves nothing: only the button sends one Telegram has verified.
            $this->askForPhone($chatId, 'phone_hint', $language);
        }
    }

    private function onStart(int $chatId, TelegramUser $from, string $code): void
    {
        $login = TelegramLogin::findByCode($code);
        $language = $login?->language ?? $from->interfaceLanguage();

        // The same person opening the link again after signing in: remind them where to go.
        if ($login !== null && $login->telegram_user_id === $from->id && $login->status->wasApproved()) {
            $this->bot->sendMessage($chatId, $this->text('approved', $language), $this->returnButton($language));

            return;
        }

        if ($login === null || ! $login->isOpen()) {
            $this->bot->sendMessage($chatId, $this->text('expired', $language));

            return;
        }

        if ($login->telegram_user_id !== null && $login->telegram_user_id !== $from->id) {
            $this->bot->sendMessage($chatId, $this->text('taken', $language));

            return;
        }

        $sent = $this->askForPhone($chatId, 'prompt', $language, [
            'device' => e($login->device),
            'minutes' => max(1, (int) ceil(now()->diffInSeconds($login->expires_at) / 60)),
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

    /** @param  array<string, mixed>  $payload */
    private function onContact(int $chatId, TelegramUser $from, TelegramLogin $login, InterfaceLanguage $language, array $payload): void
    {
        $contact = TelegramContact::fromUpdate($payload);

        if ($contact === null || ! $contact->belongsTo($from)) {
            $this->askForPhone($chatId, 'foreign_phone', $language);

            return;
        }

        $outcome = $this->settle($login, function (TelegramLogin $login) use ($from, $contact): string {
            try {
                $user = ($this->resolveAccount)($from, $contact, $login->language);
            } catch (AccountBlocked) {
                $login->update(['status' => TelegramLoginStatus::Declined]);

                return 'blocked';
            }

            // The app now has a fresh window to collect its session.
            $login->update([
                'status' => TelegramLoginStatus::Approved,
                'user_id' => $user->id,
                'approved_at' => now(),
                'expires_at' => now()->addSeconds((int) config('telegram.login_ttl_seconds')),
            ]);

            return 'approved';
        });

        $this->conclude($chatId, $language, $outcome);
    }

    private function onCancel(int $chatId, TelegramLogin $login, InterfaceLanguage $language): void
    {
        $outcome = $this->settle($login, function (TelegramLogin $login): string {
            $login->update(['status' => TelegramLoginStatus::Declined]);

            return 'declined';
        });

        $this->conclude($chatId, $language, $outcome);
    }

    /**
     * Decides the request under a row lock, so two messages racing (a double tap, "cancel" right after
     * the number) cannot both settle it. A request that is no longer open reports how it ended instead.
     *
     * @param  Closure(TelegramLogin): ('approved'|'declined'|'blocked')  $decide
     * @return 'approved'|'declined'|'blocked'|'expired'
     */
    private function settle(TelegramLogin $login, Closure $decide): string
    {
        return DB::transaction(function () use ($login, $decide): string {
            $login = TelegramLogin::query()->lockForUpdate()->findOrFail($login->id);

            if ($login->isOpen()) {
                return $decide($login);
            }

            return match (true) {
                $login->status->wasApproved() => 'approved',
                $login->status === TelegramLoginStatus::Declined => 'declined',
                default => 'expired',
            };
        });
    }

    /**
     * Tells the person how their request ended and takes the phone keyboard away.
     *
     * @param  'approved'|'declined'|'blocked'|'expired'  $outcome
     */
    private function conclude(int $chatId, InterfaceLanguage $language, string $outcome): void
    {
        $back = $outcome === 'approved' ? $this->returnButton($language) : [];

        if ($back === []) {
            $this->bot->sendMessage($chatId, $this->text($outcome, $language, ['support' => e((string) config('support.telegram_url'))]), self::REMOVE_KEYBOARD);

            return;
        }

        // A message carries a single keyboard: this one takes the phone button away, the next leads back to the app.
        $this->bot->sendMessage($chatId, $this->text('phone_saved', $language), self::REMOVE_KEYBOARD);
        $this->bot->sendMessage($chatId, $this->text('approved', $language), $back);
    }

    /**
     * Sends $key with the keyboard that shares the phone number; the text may name that button as `:button`.
     *
     * @param  array<string, string|int>  $replace
     * @return array<string, mixed> the sent message
     */
    private function askForPhone(int $chatId, string $key, InterfaceLanguage $language, array $replace = []): array
    {
        $button = $this->text('share_phone', $language);

        return $this->bot->sendMessage($chatId, $this->text($key, $language, ['button' => e($button), ...$replace]), [
            'reply_markup' => [
                'keyboard' => [
                    [['text' => $button, 'request_contact' => true]],
                    [['text' => $this->text('decline', $language)]],
                ],
                'resize_keyboard' => true,
                'input_field_placeholder' => $this->text('phone_placeholder', $language),
            ],
        ]);
    }

    /** The keyboard's cancel button arrives as its label, in whichever language it was shown. */
    private function isCancel(string $text): bool
    {
        foreach (InterfaceLanguage::cases() as $language) {
            if ($text === $this->text('decline', $language)) {
                return true;
            }
        }

        return false;
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
