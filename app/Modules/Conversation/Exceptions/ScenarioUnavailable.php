<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Exceptions;

use App\Shared\Http\Problem;
use DomainException;
use Illuminate\Http\JsonResponse;

/** The scenario does not exist, is switched off, or has no brief in the requested language yet. */
final class ScenarioUnavailable extends DomainException
{
    public function __construct(public readonly string $scenarioId, public readonly string $language)
    {
        parent::__construct("Scenario [{$scenarioId}] is not available in [{$language}]");
    }

    public function render(): JsonResponse
    {
        return Problem::make(404, 'scenario_unavailable');
    }
}
