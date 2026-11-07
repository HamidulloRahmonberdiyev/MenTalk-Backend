<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Actions;

use App\Modules\Ai\Contracts\AiClient;
use App\Modules\Conversation\DTO\TurnInput;
use App\Modules\Conversation\DTO\TurnResult;
use App\Modules\Conversation\Enums\TurnKind;
use App\Modules\Conversation\Models\Conversation;
use App\Modules\Conversation\Models\Turn;
use App\Modules\Conversation\Services\TurnHistory;
use App\Modules\Conversation\Services\TurnRequests;
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
        private TurnRequests $requests,
        private SpeechSynthesizer $speech,
        private TurnHistory $history,
        private LanguageCatalog $languages,
    ) {}

    public function __invoke(Conversation $conversation, TurnInput $input): Turn
    {
        $conversation->loadMissing(['scenario', 'user.profile']);
        $language = $this->languages->get($conversation->target_language);

        return Cache::lock("conversation:{$conversation->id}", 40)->block(3, function () use ($conversation, $input, $language) {
            $result = TurnResult::from($this->ai->json($this->requests->make($conversation, $input, $language)));
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
