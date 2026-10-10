<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;

final readonly class CardState
{
    public function __construct(
        public int $stage,
        public CarbonImmutable $dueAt,
        public int $streak,
        public int $lapses,
    ) {}
}
