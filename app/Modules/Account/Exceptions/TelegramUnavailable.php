<?php

declare(strict_types=1);

namespace App\Modules\Account\Exceptions;

use App\Shared\Http\Problem;
use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * The Bot API refused or could not be reached, or no bot is configured. Messages never carry the
 * request URL: it contains the bot token.
 */
final class TelegramUnavailable extends RuntimeException
{
    public function __construct(string $message, public readonly ?int $errorCode = null)
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return Problem::make(503, 'telegram_unavailable');
    }
}
