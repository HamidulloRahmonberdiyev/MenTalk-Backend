<?php

declare(strict_types=1);

namespace App\Services\Push;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Expo's push service, which hands messages to FCM (Android) and APNs (iOS). The Firebase key
 * lives with Expo, so this server needs no credentials of its own.
 *
 * @see https://docs.expo.dev/push-notifications/sending-notifications/
 */
final class ExpoPushClient
{
    /** Expo's limit per request. */
    public const MAX_MESSAGES = 100;

    private const URL = 'https://exp.host/--/api/v2/push';

    /**
     * @param  list<array<string, mixed>>  $messages  at most MAX_MESSAGES
     * @return list<array<string, mixed>> one ticket per message, in the same order
     */
    public function send(array $messages): array
    {
        return $this->request()->post(self::URL.'/send', $messages)->throw()->json('data', []);
    }

    /**
     * @param  list<string>  $ticketIds
     * @return array<string, array<string, mixed>> by ticket id; ids without a receipt yet are left out
     */
    public function receipts(array $ticketIds): array
    {
        return $this->request()->post(self::URL.'/getReceipts', ['ids' => $ticketIds])->throw()->json('data', []);
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()->asJson()->timeout(15)->retry([500, 2000], when: self::isTemporary(...));
    }

    /** Expo asks for retries on 429 and 5xx only: any other 4xx would fail the same way again. */
    private static function isTemporary(Throwable $error): bool
    {
        return $error instanceof ConnectionException
            || ($error instanceof RequestException && ($error->response->status() === 429 || $error->response->serverError()));
    }
}
