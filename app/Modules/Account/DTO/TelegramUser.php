<?php

declare(strict_types=1);

namespace App\Modules\Account\DTO;

use App\Modules\Language\Enums\InterfaceLanguage;
use Illuminate\Support\Str;

/** The `from` of a Telegram update: who wrote to the bot or tapped its button. */
final readonly class TelegramUser
{
    public function __construct(
        public int $id,
        public string $firstName,
        public ?string $lastName = null,
        public ?string $username = null,
        public ?string $languageCode = null,
        public bool $isBot = false,
    ) {}

    /** @param  array<string, mixed>  $from */
    public static function fromUpdate(array $from): self
    {
        return new self(
            (int) $from['id'],
            (string) ($from['first_name'] ?? ''),
            isset($from['last_name']) ? (string) $from['last_name'] : null,
            isset($from['username']) ? (string) $from['username'] : null,
            isset($from['language_code']) ? (string) $from['language_code'] : null,
            (bool) ($from['is_bot'] ?? false),
        );
    }

    public function displayName(): string
    {
        $name = Str::of("{$this->firstName} {$this->lastName}")->squish()->limit(60, '')->toString();

        return $name !== '' ? $name : ($this->username ?? 'Telegram');
    }

    /** The bot speaks the person's Telegram language when the app did not say which one to use. */
    public function interfaceLanguage(): InterfaceLanguage
    {
        return match (Str::lower(Str::substr((string) $this->languageCode, 0, 2))) {
            'en' => InterfaceLanguage::English,
            'ru', 'uk', 'be', 'kk', 'ky', 'tg' => InterfaceLanguage::Russian,
            default => InterfaceLanguage::Uzbek,
        };
    }
}
