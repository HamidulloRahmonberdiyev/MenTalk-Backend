<?php

declare(strict_types=1);

namespace App\Modules\Ai\Contracts;

use App\Modules\Ai\DTO\AiRequest;

interface AiClient
{
    public function json(AiRequest $request): array;

    public function text(AiRequest $request): string;
}
