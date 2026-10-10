<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\DTO\TurnInput;
use App\Enums\TurnKind;
use App\Models\LearnerProfile;
use App\Models\Scenario;

final readonly class PromptBuilder
{
    public function __construct(private PromptStore $prompts) {}

    public function system(Scenario $scenario, LearnerProfile $learner): string
    {
        return strtr($this->prompts->get('tutor.system'), [
            '{scenario}' => $scenario->brief,
            '{topics}' => $scenario->next_topics,
            '{learner}' => $learner->describe(),
            '{level}' => $this->prompts->get('level.'.($learner->level?->value ?? 'beginner')),
            '{gender}' => $this->prompts->get('gender.'.($learner->gender?->value ?? 'unspecified')),
            '{ui_language}' => $learner->uiLanguageName(),
            '{flow_rules}' => $this->prompts->get('tutor.flow'),
        ]);
    }

    public function userParts(TurnInput $input): array
    {
        return match ($input->kind) {
            TurnKind::Opening => [['text' => $this->prompts->get('turn.opening')]],
            TurnKind::Text => [['text' => "The learner typed: {$input->text}"]],
            TurnKind::Audio => [
                ['text' => $this->prompts->get('turn.audio')],
                ['inlineData' => ['mimeType' => $input->mime, 'data' => $input->audio]],
            ],
        };
    }
}
