<?php

declare(strict_types=1);

namespace App\Services\Push;

use App\DTO\NotificationMessage;
use App\Enums\InterfaceLanguage;
use App\Jobs\CheckPushReceipts;
use App\Models\PushToken;
use Illuminate\Support\Facades\Log;

/** Puts an inbox notification on the learners' phones, in the language each of them uses. */
final readonly class PushSender
{
    /** Tells the app a push is one of ours, next to its own local reminders. */
    private const SOURCE = 'mentalk-push';

    /** Created by the app; Android drops a push whose channel does not exist. */
    private const CHANNEL = 'inbox';

    /** Expo recommends waiting this long before asking how delivery went. */
    private const RECEIPT_DELAY_MINUTES = 15;

    public function __construct(private ExpoPushClient $expo) {}

    /** @param  array<string, string>  $recipients  notification id by user id */
    public function send(array $recipients, NotificationMessage $message): void
    {
        $tokens = PushToken::query()
            ->whereIn('user_id', array_keys($recipients))
            ->deliverable()
            ->with('user.profile')
            ->get();

        foreach ($tokens->chunk(ExpoPushClient::MAX_MESSAGES) as $batch) {
            $batch = $batch->values();
            $tickets = $this->expo->send($batch->map(
                fn (PushToken $token) => $this->payload($token, $message, $recipients[$token->user_id]),
            )->all());

            $this->settle($batch->pluck('token')->all(), $tickets);
        }
    }

    private function payload(PushToken $token, NotificationMessage $message, string $notificationId): array
    {
        $language = $token->user->profile?->interfaceLanguage() ?? InterfaceLanguage::Uzbek;

        return [
            'to' => $token->token,
            'title' => NotificationMessage::render($message->title, $language),
            'body' => NotificationMessage::render($message->body, $language),
            'data' => [
                'source' => self::SOURCE,
                'id' => $notificationId,
                'kind' => $message->kind->value,
                'action' => $message->action?->value,
            ],
            'sound' => 'default',
            'channelId' => self::CHANNEL,
            // Normal priority may wait for the phone to wake up; these should arrive now.
            'priority' => 'high',
        ];
    }

    /**
     * @param  list<string>  $tokens
     * @param  list<array<string, mixed>>  $tickets
     */
    private function settle(array $tokens, array $tickets): void
    {
        $pending = [];

        foreach ($tickets as $index => $ticket) {
            if (($ticket['status'] ?? null) === 'ok') {
                $pending[$ticket['id']] = $tokens[$index];
            } else {
                self::reportFailure($tokens[$index], $ticket);
            }
        }

        if ($pending !== []) {
            CheckPushReceipts::dispatch($pending)->delay(now()->addMinutes(self::RECEIPT_DELAY_MINUTES));
        }
    }

    /**
     * A ticket or receipt that failed. An uninstalled app means the token is dead; anything else
     * (wrong Firebase key, oversized message) is ours to fix, so it goes to the log.
     *
     * @param  array<string, mixed>  $result
     */
    public static function reportFailure(string $token, array $result): void
    {
        $error = $result['details']['error'] ?? null;

        if ($error === 'DeviceNotRegistered') {
            PushToken::query()->where('token', $token)->delete();

            return;
        }

        Log::warning('Push not delivered', ['error' => $error, 'message' => $result['message'] ?? null]);
    }
}
