<?php

declare(strict_types=1);

namespace App\Actions\Conversation;

use App\Contracts\AiClient;
use App\Contracts\SpeechSynthesizer;
use App\DTO\AiRequest;
use App\DTO\TurnInput;
use App\DTO\TurnResult;
use App\Enums\TurnKind;
use App\Models\Conversation;
use App\Models\Turn;
use App\Services\Ai\PromptBuilder;
use App\Services\Ai\Schemas\TurnSchema;
use App\Services\Conversation\TurnHistory;
use Illuminate\Support\Facades\Cache;

final readonly class SendTurn
{
    public function __construct(
        private AiClient $ai,
        private PromptBuilder $prompts,
        private SpeechSynthesizer $speech,
        private TurnHistory $history,
    ) {}

    public function __invoke(Conversation $conversation, TurnInput $input): Turn
    {
        $conversation->loadMissing(['scenario', 'user.profile']);

        return Cache::lock("conversation:{$conversation->id}", 40)->block(3, function () use ($conversation, $input) {
            $request = new AiRequest(
                system: $this->prompts->system($conversation->scenario, $conversation->user->profile),
                contents: [
                    ...$this->history->contents($conversation),
                    ['role' => 'user', 'parts' => $this->prompts->userParts($input)],
                ],
                schema: TurnSchema::get(),
            );
            $result = TurnResult::from($this->ai->json($request));
            $clip = $this->speech->speak($result->reply, config('ai.voice'));

            return $this->history->append($conversation, $input, $result, $clip->url)
                ->setAttribute('audio_ms', $clip->durationMs)
                ->setAttribute('transcript', $input->kind === TurnKind::Text ? $input->text : $result->transcript);
        });
    }
}
