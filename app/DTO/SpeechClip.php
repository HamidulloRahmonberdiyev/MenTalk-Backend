<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class SpeechClip
{
    public function __construct(public string $url, public int $durationMs) {}
}
