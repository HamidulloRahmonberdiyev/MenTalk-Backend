<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Contracts\AiClient;
use App\DTO\AiRequest;
use App\Exceptions\AiUnavailable;

final readonly class GeminiClient implements AiClient
{
    public function __construct(private GeminiTransport $transport, private array $models) {}

    public function json(AiRequest $request): array
    {
        return json_decode($this->textOf($request), true, 512, JSON_THROW_ON_ERROR);
    }

    public function text(AiRequest $request): string
    {
        return trim($this->textOf($request));
    }

    private function textOf(AiRequest $request): string
    {
        $response = $this->generate($request);

        return $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    private function generate(AiRequest $request): array
    {
        foreach ([$request->model ?? $this->models['primary'], $this->models['fallback']] as $model) {
            try {
                return $this->transport->generate($model, $request->payload());
            } catch (AiUnavailable) {
                continue;
            }
        }

        throw new AiUnavailable;
    }
}
