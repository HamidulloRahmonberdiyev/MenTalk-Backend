<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;

/** The scenario does not exist, is switched off, or has no brief in the requested language yet. */
final class ScenarioUnavailable extends DomainException
{
    public function __construct(public readonly string $scenarioId, public readonly string $language)
    {
        parent::__construct("Scenario [{$scenarioId}] is not available in [{$language}]");
    }
}
