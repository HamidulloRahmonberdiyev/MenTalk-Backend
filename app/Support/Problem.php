<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\JsonResponse;

final class Problem
{
    public static function make(int $status, string $type, array $headers = []): JsonResponse
    {
        return response()->json(
            ['type' => $type, 'status' => $status],
            $status,
            $headers + ['Content-Type' => 'application/problem+json'],
        );
    }
}
