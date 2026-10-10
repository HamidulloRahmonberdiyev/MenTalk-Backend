<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Contracts\AiClient;
use App\DTO\AiRequest;

final class FakeAiClient implements AiClient
{
    /** @var list<AiRequest> */
    public array $requests = [];

    public function __construct(private readonly array $json = [], private readonly string $text = 'ok') {}

    public function json(AiRequest $request): array
    {
        $this->requests[] = $request;

        return $this->json;
    }

    public function text(AiRequest $request): string
    {
        $this->requests[] = $request;

        return $this->text;
    }
}
