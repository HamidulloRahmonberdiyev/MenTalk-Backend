<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Services;

use App\Modules\Ai\Services\PromptRenderer;
use App\Modules\Conversation\DTO\TurnInput;
use App\Modules\Conversation\Enums\TurnKind;
use App\Modules\Conversation\Models\Conversation;
use App\Modules\Language\DTO\TutorLanguage;

/** Assembles the tutor's prompts for one conversation, in the language it is held in. */
final readonly class PromptBuilder
{
    public function __construct(private PromptRenderer $renderer) {}

    public function system(Conversation $conversation, TutorLanguage $language): string
    {
        $learner = $conversation->user->profile;
        $brief = $conversation->scenario->briefFor($language->code);

        return $this->renderer->render('tutor.system', $language, [
            '{scenario}' => $this->renderer->fill($brief->brief, $language),
            '{topics}' => $brief->next_topics,
            '{learner}' => $learner->describe(),
            '{level}' => $this->renderer->render('level.'.($learner->level?->value ?? 'beginner'), $language),
            '{gender}' => $this->renderer->render('gender.'.($learner->gender?->value ?? 'unspecified'), $language),
            '{ui_language}' => $learner->interfaceLanguage()->englishName(),
            '{flow_rules}' => $this->renderer->render('tutor.flow', $language),
        ]);
    }

    public function userParts(TurnInput $input, TutorLanguage $language): array
    {
        return match ($input->kind) {
            TurnKind::Opening => [['text' => $this->renderer->render('turn.opening', $language)]],
            TurnKind::Text => [['text' => "The learner typed: {$input->text}"]],
            TurnKind::Audio => [
                ['text' => $this->renderer->render('turn.audio', $language)],
                ['inlineData' => ['mimeType' => $input->mime, 'data' => $input->audio]],
            ],
        };
    }
}
