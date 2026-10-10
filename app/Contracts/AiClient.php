<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\AiRequest;

interface AiClient
{
    public function json(AiRequest $request): array;

    public function text(AiRequest $request): string;
}
