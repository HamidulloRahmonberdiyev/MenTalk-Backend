<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class AiRequest
{
    public function __construct(
        public string $system,
        public array $contents,
        public ?array $schema = null,
        public float $temperature = 0.8,
        public ?string $model = null,
    ) {}

    public function payload(): array
    {
        return [
            'systemInstruction' => ['parts' => [['text' => $this->system]]],
            'contents' => $this->contents,
            'generationConfig' => array_filter([
                'temperature' => $this->temperature,
                'responseMimeType' => $this->schema ? 'application/json' : null,
                'responseSchema' => $this->schema,
            ], static fn ($value) => $value !== null),
        ];
    }
}
