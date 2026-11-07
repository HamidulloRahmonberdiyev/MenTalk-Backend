<?php

declare(strict_types=1);

namespace App\Modules\Ai\Contracts;

use App\Modules\Ai\DTO\AiRequest;

interface AiClient
{
    public function json(AiRequest $request): array;

    public function text(AiRequest $request): string;

    /**
     * Same answer as `json()`, but every piece of the raw JSON text is handed to `$onText` the moment
     * the model writes it, so a caller can show the reply before the whole object is done.
     *
     * @param  callable(string): void  $onText
     *
     * @throws \JsonException when the finished text is not valid JSON
     */
    public function streamJson(AiRequest $request, callable $onText): array;
}
