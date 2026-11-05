<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\DTO;

use App\Modules\Vocabulary\Enums\ReviewRating;
use Carbon\CarbonImmutable;

final readonly class ReviewAnswer
{
    public function __construct(
        /** The server's row id; null when the device has not synced the word yet. */
        public ?string $id,
        public string $word,
        public string $language,
        public ReviewRating $rating,
        public CarbonImmutable $answeredAt,
        public ?int $durationMs,
    ) {}

    public static function fromArray(array $data, string $defaultLanguage): self
    {
        return new self(
            id: $data['id'] ?? null,
            word: $data['word'],
            language: $data['language'] ?? $defaultLanguage,
            // Clients that predate ratings only send right or wrong.
            rating: isset($data['rating']) ? ReviewRating::from((int) $data['rating']) : ReviewRating::fromCorrect((bool) $data['correct']),
            answeredAt: CarbonImmutable::parse($data['answered_at']),
            durationMs: isset($data['duration_ms']) ? (int) $data['duration_ms'] : null,
        );
    }
}
