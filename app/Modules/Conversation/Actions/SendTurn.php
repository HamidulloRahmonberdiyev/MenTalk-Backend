<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Actions;

use App\Modules\Ai\Contracts\AiClient;
use App\Modules\Ai\DTO\AiRequest;
use App\Modules\Conversation\DTO\TurnInput;
use App\Modules\Conversation\DTO\TurnResult;
use App\Modules\Conversation\Enums\TurnKind;
use App\Modules\Conversation\Models\Conversation;
use App\Modules\Conversation\Models\Turn;
use App\Modules\Conversation\Schemas\TurnSchema;
use App\Modules\Conversation\Services\PromptBuilder;
use App\Modules\Conversation\Services\TurnHistory;
use App\Modules\Language\Contracts\LanguageCatalog;
use App\Modules\Language\DTO\TutorLanguage;
use App\Modules\Speech\Contracts\SpeechSynthesizer;
use App\Modules\Speech\DTO\SpeechClip;
use Illuminate\Support\Facades\Cache;
use Throwable;

final readonly class SendTurn
{
    public function __construct(
        private AiClient $ai,
        private PromptBuilder $prompts,
        private SpeechSynthesizer $speech,
        private TurnHistory $history,
        private LanguageCatalog $languages,
    ) {}

    public function __invoke(Conversation $conversation, TurnInput $input): Turn
    {
        $conversation->loadMissing(['scenario', 'user.profile']);
        $language = $this->languages->get($conversation->target_language);

        return Cache::lock("conversation:{$conversation->id}", 40)->block(3, function () use ($conversation, $input, $language) {
            $request = new AiRequest(
                system: $this->prompts->system($conversation, $language),
                contents: [
                    ...$this->history->contents($conversation),
                    ['role' => 'user', 'parts' => $this->prompts->userParts($input, $language)],
                ],
                schema: TurnSchema::get(),
            );
            $result = TurnResult::from($this->ai->json($request));
            $clip = $this->speak($result->reply, $language);

            return $this->history->append($conversation, $input, $result, $clip?->url)
                ->setAttribute('audio_ms', $clip?->durationMs)
                ->setAttribute('transcript', $input->kind === TurnKind::Text ? $input->text : $result->transcript);
        });
    }

    /** Voice is a nice-to-have: without it the reply still goes out and the app reads it with the device voice. */
    private function speak(string $text, TutorLanguage $language): ?SpeechClip
    {
        try {
            return $this->speech->speak($text, $language->voice ?? config('ai.voice'));
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }
}
