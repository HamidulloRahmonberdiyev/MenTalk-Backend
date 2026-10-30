<?php

declare(strict_types=1);

namespace App\Actions\Conversation;

use App\Contracts\AiClient;
use App\Contracts\LanguageCatalog;
use App\DTO\AiRequest;
use App\Models\Conversation;
use App\Services\Ai\PromptRenderer;
use App\Services\Conversation\TurnHistory;

final readonly class RequestHint
{
    public function __construct(
        private AiClient $ai,
        private PromptRenderer $prompts,
        private TurnHistory $history,
        private LanguageCatalog $languages,
    ) {}

    public function __invoke(Conversation $conversation): string
    {
        return $this->ai->text(new AiRequest(
            system: $this->prompts->render('hint', $this->languages->get($conversation->target_language)),
            contents: [
                ...$this->history->contents($conversation),
                ['role' => 'user', 'parts' => [['text' => '(suggest what the learner could say next)']]],
            ],
            temperature: 0.7,
        ));
    }
}
