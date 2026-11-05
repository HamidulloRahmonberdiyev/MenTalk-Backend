<?php

declare(strict_types=1);

namespace App\Modules\Ai\Exceptions;

use App\Shared\Http\Problem;
use Illuminate\Http\JsonResponse;
use RuntimeException;

final class AiRejected extends RuntimeException
{
    public function render(): JsonResponse
    {
        return Problem::make(502, 'turn_failed');
    }
}
