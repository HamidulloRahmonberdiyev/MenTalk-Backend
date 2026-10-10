<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class TurnResult
{
    public function __construct(
        public string $transcript,
        public string $reply,
        public ?string $emotion,
        public array $mistakes,
    ) {}

    public static function from(array $raw): self
    {
        return new self(
            trim((string) ($raw['transcript'] ?? '')),
            trim((string) ($raw['reply'] ?? '')),
            $raw['emotion'] ?? null,
            array_values($raw['mistakes'] ?? []),
        );
    }
}
