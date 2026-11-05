<?php

declare(strict_types=1);

namespace App\Modules\Certificate\DTO;

use App\Modules\Account\Enums\Level;

/** How far a learner is from a certificate in the language they study. */
final readonly class CertificateStanding
{
    /** @param  array{speaking: float, vocabulary: float, grammar: float, overall: float}|null  $scores  null before any rated conversation */
    public function __construct(
        public string $language,
        public ?Level $level,
        public ?int $placementScore,
        public int $conversations,
        public int $requiredConversations,
        public ?array $scores,
    ) {}

    /** A level picked by hand proves nothing, so only one measured by the test counts. */
    public function isPlaced(): bool
    {
        return $this->level !== null && $this->placementScore !== null;
    }

    public function isEligible(): bool
    {
        return $this->isPlaced() && $this->scores !== null && $this->conversations >= $this->requiredConversations;
    }
}
