<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Services;

use App\Modules\Ai\DTO\AiRequest;
use App\Modules\Conversation\DTO\TurnInput;
use App\Modules\Conversation\Models\Conversation;
use App\Modules\Conversation\Schemas\TurnSchema;
use App\Modules\Language\DTO\TutorLanguage;

/** The model request for one learner turn; shared by the classic and the streamed endpoint. */
final readonly class TurnRequests
{
    public function __construct(private PromptBuilder $prompts, private TurnHistory $history) {}

    public function make(Conversation $conversation, TurnInput $input, TutorLanguage $language): AiRequest
    {
        return new AiRequest(
            system: $this->prompts->system($conversation, $language),
            contents: [
                ...$this->history->contents($conversation),
                ['role' => 'user', 'parts' => $this->prompts->userParts($input, $language)],
            ],
            schema: TurnSchema::get(),
        );
    }
}
