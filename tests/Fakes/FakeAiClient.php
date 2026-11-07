<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Modules\Ai\Contracts\AiClient;
use App\Modules\Ai\DTO\AiRequest;

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

    /** Hands the JSON over a few characters at a time, the way a real stream splits it. */
    public function streamJson(AiRequest $request, callable $onText): array
    {
        $this->requests[] = $request;

        foreach (mb_str_split(json_encode($this->json, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 7) as $piece) {
            $onText($piece);
        }

        return $this->json;
    }

    public function text(AiRequest $request): string
    {
        $this->requests[] = $request;

        return $this->text;
    }
}
