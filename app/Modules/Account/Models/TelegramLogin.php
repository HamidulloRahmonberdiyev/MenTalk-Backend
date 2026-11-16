<?php

declare(strict_types=1);

namespace App\Modules\Account\Models;

use App\Modules\Account\Enums\TelegramLoginStatus;
use App\Modules\Language\Enums\InterfaceLanguage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One "sign in with Telegram" attempt. The app holds the secret and polls; the bot learns the code
 * from the deep link, shows a confirmation, and on "confirm" ties the attempt to an account.
 *
 * @property TelegramLoginStatus $status
 * @property ?InterfaceLanguage $language
 */
final class TelegramLogin extends Model
{
    use HasUlids, MassPrunable;

    /** Long finished: kept a day for support questions, then pruned. */
    private const RETENTION_HOURS = 24;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => TelegramLoginStatus::class,
            'language' => InterfaceLanguage::class,
            'telegram_user_id' => 'integer',
            'chat_id' => 'integer',
            'message_id' => 'integer',
            'expires_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public static function hash(string $value): string
    {
        return hash('sha256', $value);
    }

    public static function findByCode(string $code): ?self
    {
        return self::query()->where('code_hash', self::hash($code))->first();
    }

    public function hasSecret(string $secret): bool
    {
        return hash_equals($this->secret_hash, self::hash($secret));
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /** Still waiting for the person in Telegram. */
    public function isOpen(): bool
    {
        return $this->status->isWaiting() && ! $this->isExpired();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function prunable(): Builder
    {
        return self::query()->where('created_at', '<', now()->subHours(self::RETENTION_HOURS));
    }
}
