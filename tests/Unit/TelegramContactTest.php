<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Account\DTO\TelegramContact;
use App\Modules\Account\DTO\TelegramUser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TelegramContactTest extends TestCase
{
    public static function numbers(): array
    {
        return [
            'without the plus (most clients)' => ['998901234567', '+998901234567'],
            'with the plus' => ['+998901234567', '+998901234567'],
            'formatted' => ['+998 (90) 123-45-67', '+998901234567'],
            'longest E.164' => ['123456789012345', '+123456789012345'],
        ];
    }

    #[DataProvider('numbers')]
    public function test_numbers_are_stored_as_e164(string $sent, string $stored): void
    {
        $this->assertSame($stored, TelegramContact::fromUpdate(['phone_number' => $sent, 'user_id' => 1])?->phone);
    }

    public static function nonsense(): array
    {
        return [
            'empty' => [''],
            'too short' => ['12345'],
            'too long' => ['1234567890123456'],
            'leading zero' => ['0901234567'],
            'missing' => [null],
        ];
    }

    #[DataProvider('nonsense')]
    public function test_implausible_numbers_are_refused(?string $sent): void
    {
        $this->assertNull(TelegramContact::fromUpdate(array_filter(['phone_number' => $sent, 'user_id' => 1])));
    }

    public function test_only_the_senders_own_number_belongs_to_them(): void
    {
        $sender = new TelegramUser(42, 'Ali');

        $this->assertTrue(TelegramContact::fromUpdate(['phone_number' => '998901234567', 'user_id' => 42])->belongsTo($sender));
        $this->assertFalse(TelegramContact::fromUpdate(['phone_number' => '998901234567', 'user_id' => 43])->belongsTo($sender));
        // A card for someone who is not on Telegram has no user id at all.
        $this->assertFalse(TelegramContact::fromUpdate(['phone_number' => '998901234567'])->belongsTo($sender));
    }
}
