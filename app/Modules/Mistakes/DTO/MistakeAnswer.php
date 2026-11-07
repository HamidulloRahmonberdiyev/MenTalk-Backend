<?php

declare(strict_types=1);

namespace App\Modules\Mistakes\DTO;

use App\Modules\Vocabulary\Enums\ReviewRating;
use Carbon\CarbonImmutable;

final readonly class MistakeAnswer
{
    public function __construct(
        public string $id,
        public ReviewRating $rating,
        /** Which drill it was, kept for statistics: choice, spot, build. */
        public ?string $exercise,
        public CarbonImmutable $answeredAt,
        public ?int $durationMs,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            rating: isset($data['rating']) ? ReviewRating::from((int) $data['rating']) : ReviewRating::fromCorrect((bool) $data['correct']),
            exercise: $data['exercise'] ?? null,
            answeredAt: CarbonImmutable::parse($data['answered_at']),
            durationMs: isset($data['duration_ms']) ? (int) $data['duration_ms'] : null,
        );
    }
}
