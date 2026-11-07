<?php

declare(strict_types=1);

namespace App\Modules\Certificate\DTO;

use App\Modules\Account\Enums\Level;

/** What a learner has done in the language they study, and the certificate level it supports. */
final readonly class CertificateStanding
{
    /**
     * `$scores` is null when there is nothing to score; a skill no activity measured is null on its own.
     *
     * @param  array{speaking: ?float, vocabulary: ?float, grammar: ?float, overall: float}|null  $scores
     */
    public function __construct(
        public string $language,
        /** Worked out from the evidence (`LevelEstimate`); null when there is none. */
        public ?Level $level,
        /** Only for a level measured by the placement test, not one picked by hand. */
        public ?int $placementScore,
        public int $conversations,
        public int $requiredConversations,
        public int $words,
        public ?array $scores,
    ) {}

    public function isPlaced(): bool
    {
        return $this->placementScore !== null;
    }

    /** Any activity at all earns a certificate; doing nothing earns none. */
    public function isEligible(): bool
    {
        return $this->level !== null && $this->scores !== null;
    }

    /** The values a certificate copies, to tell whether an issued one is still current. */
    public function snapshot(): array
    {
        return [
            'level' => $this->level?->value,
            'placement_score' => $this->placementScore,
            'speaking' => $this->scores['speaking'] ?? null,
            'vocabulary' => $this->scores['vocabulary'] ?? null,
            'grammar' => $this->scores['grammar'] ?? null,
            'overall' => $this->scores['overall'] ?? null,
            'conversations' => $this->conversations,
            'words' => $this->words,
        ];
    }
}
