<?php

declare(strict_types=1);

namespace App\Modules\Account\DTO;

/**
 * The `contact` of a message: a phone number someone sent the bot.
 *
 * @see https://core.telegram.org/bots/api#contact
 */
final readonly class TelegramContact
{
    /** @param  string  $phone  E.164, e.g. "+998901234567" */
    public function __construct(public string $phone, public ?int $userId) {}

    /**
     * Null when the number is not a plausible E.164 one.
     *
     * @param  array<string, mixed>  $contact
     */
    public static function fromUpdate(array $contact): ?self
    {
        // Depending on the client, Telegram sends the number with or without the "+".
        $digits = preg_replace('/\D+/', '', (string) ($contact['phone_number'] ?? ''));

        if (preg_match('/^[1-9]\d{6,14}$/', (string) $digits) !== 1) {
            return null;
        }

        return new self('+'.$digits, isset($contact['user_id']) ? (int) $contact['user_id'] : null);
    }

    /**
     * Whether this is the sender's own number. The "share phone" button sends exactly that, verified by
     * Telegram; a contact card picked from the address book can be anyone's and proves nothing.
     */
    public function belongsTo(TelegramUser $sender): bool
    {
        return $this->userId === $sender->id;
    }
}
