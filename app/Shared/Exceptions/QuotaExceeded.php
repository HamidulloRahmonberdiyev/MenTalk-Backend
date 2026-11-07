<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use App\Shared\Http\Problem;
use Illuminate\Http\JsonResponse;
use RuntimeException;

/** Today's allowance of a `DailyQuota` bucket is used up; answers like the middleware does. */
final class QuotaExceeded extends RuntimeException
{
    public function render(): JsonResponse
    {
        return Problem::make(429, 'quota');
    }
}
