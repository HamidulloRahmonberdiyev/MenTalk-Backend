<?php

declare(strict_types=1);

namespace App\Modules\Account\Exceptions;

use App\Shared\Http\Problem;
use Illuminate\Http\JsonResponse;
use RuntimeException;

final class AccountBlocked extends RuntimeException
{
    public function render(): JsonResponse
    {
        return Problem::make(403, 'blocked');
    }
}
