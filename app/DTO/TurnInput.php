<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\TurnKind;

final readonly class TurnInput
{
    public function __construct(
        public TurnKind $kind,
        public string $text = '',
        public string $audio = '',
        public string $mime = '',
    ) {}
}
