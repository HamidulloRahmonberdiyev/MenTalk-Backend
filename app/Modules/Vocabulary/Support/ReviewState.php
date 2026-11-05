<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Support;

use App\Modules\Vocabulary\Enums\WordStatus;
use Carbon\CarbonImmutable;

final readonly class ReviewState
{
    public function __construct(
        public WordStatus $status,
        public float $ease,
        public int $intervalDays,
        public int $repetitions,
        public int $lapses,
        public CarbonImmutable $dueAt,
    ) {}

    public static function fresh(CarbonImmutable $now): self
    {
        return new self(WordStatus::New, Srs::START_EASE, 0, 0, 0, $now);
    }
}
