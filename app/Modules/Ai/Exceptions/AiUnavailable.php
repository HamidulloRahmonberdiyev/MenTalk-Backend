<?php

declare(strict_types=1);

namespace App\Modules\Ai\Exceptions;

use App\Shared\Http\Problem;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class AiUnavailable extends RuntimeException
{
    public function render(): JsonResponse
    {
        return Problem::make(503, 'unavailable', ['Retry-After' => 5]);
    }
}
