<?php

declare(strict_types=1);

namespace App\Actions\Conversation;

use App\Contracts\AiClient;
use App\DTO\AiRequest;
use App\Models\Conversation;
use App\Services\Ai\PromptStore;
use App\Services\Conversation\TurnHistory;

final readonly class RequestHint
{
    public function __construct(
        private AiClient $ai,
        private PromptStore $prompts,
        private TurnHistory $history,
    ) {}

    public function __invoke(Conversation $conversation): string
    {
        return $this->ai->text(new AiRequest(
            system: $this->prompts->get('hint'),
            contents: [
                ...$this->history->contents($conversation),
                ['role' => 'user', 'parts' => [['text' => '(suggest what the learner could say next)']]],
            ],
            temperature: 0.7,
        ));
    }
}
