<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Actions;

use App\Modules\Ai\Contracts\AiClient;
use App\Modules\Ai\DTO\AiRequest;
use App\Modules\Ai\Services\PromptRenderer;
use App\Modules\Conversation\Models\Conversation;
use App\Modules\Conversation\Services\TurnHistory;
use App\Modules\Language\Contracts\LanguageCatalog;

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
