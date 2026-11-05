<?php

declare(strict_types=1);

namespace App\Modules\Speech\DTO;

final readonly class SpeechClip
{
    public function __construct(public string $url, public int $durationMs) {}

    /** @param  array{url: string, duration_ms: int}  $clip */
    public static function fromArray(array $clip): self
    {
        return new self($clip['url'], $clip['duration_ms']);
    }

    /** @return array{url: string, duration_ms: int} */
    public function toArray(): array
    {
        return ['url' => $this->url, 'duration_ms' => $this->durationMs];
    }
}
