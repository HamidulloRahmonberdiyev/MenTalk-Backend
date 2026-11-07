<?php

declare(strict_types=1);

namespace App\Modules\Mistakes\DTO;

use App\Modules\Conversation\Enums\MistakeCategory;
use App\Modules\Mistakes\Support\MistakeText;

/** A correction as the tutor made it, cleaned up; null when there is nothing to practise in it. */
final readonly class Correction
{
    private function __construct(
        public string $said,
        public string $correct,
        public string $note,
        public MistakeCategory $category,
        public string $fingerprint,
    ) {}

    public static function from(mixed $raw): ?self
    {
        if (! is_array($raw)) {
            return null;
        }

        $said = MistakeText::clean($raw['said'] ?? null);
        $correct = MistakeText::clean($raw['correct'] ?? null);

        if ($correct === '' || MistakeText::trivial($said, $correct)) {
            return null;
        }

        return new self(
            $said,
            $correct,
            MistakeText::clean($raw['note'] ?? null),
            MistakeCategory::parse($raw['category'] ?? null),
            MistakeText::fingerprint($said, $correct),
        );
    }
}
