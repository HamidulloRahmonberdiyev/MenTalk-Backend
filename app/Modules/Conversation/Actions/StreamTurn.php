<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Actions;

use App\Modules\Ai\Contracts\AiClient;
use App\Modules\Ai\Exceptions\AiRejected;
use App\Modules\Conversation\DTO\TurnInput;
use App\Modules\Conversation\DTO\TurnResult;
use App\Modules\Conversation\Enums\TurnKind;
use App\Modules\Conversation\Models\Conversation;
use App\Modules\Conversation\Models\Turn;
use App\Modules\Conversation\Services\JsonStringTap;
use App\Modules\Conversation\Services\SpokenLine;
use App\Modules\Conversation\Services\TurnHistory;
use App\Modules\Conversation\Services\TurnRequests;
use App\Modules\Conversation\Services\TutorPersona;
use App\Modules\Speech\Contracts\SpeechSynthesizer;
use Closure;
use JsonException;

/**
 * One learner turn, answered as events while it is produced (see `POST .../turns/stream`):
 *
 * - `transcript` as soon as the model has written down what the learner said (voice turns);
 * - `delta`… the reply word by word, then `reply` with the finished text;
 * - `audio` for the voice. The first piece is synthesized the moment the reply text is complete, while
 *   the model is still writing up the mistakes, so the voice is ready about when the text is;
 * - `saved` with the turn's id once it is stored, so the app can ask for a voice that failed again;
 * - `done` once every piece is voiced.
 *
 * The voice comes as described in {@see SpokenLine}.
 */
final readonly class StreamTurn
{
    public function __construct(
        private AiClient $ai,
        private TurnRequests $requests,
        private SpeechSynthesizer $speech,
        private TurnHistory $history,
        private TutorPersona $persona,
    ) {}

    /**
     * @param  Closure(string, array<string, mixed>): void  $emit
     * @param  Closure(): void  $release  frees the conversation for the next turn; voicing needs no lock
     */
    public function __invoke(Conversation $conversation, TurnInput $input, bool $clips, Closure $emit, Closure $release): void
    {
        $conversation->loadMissing(['scenario', 'user.profile']);
        $language = $this->persona->language($conversation);
        $line = new SpokenLine($this->speech, $this->persona->voice($conversation), $clips, $emit);

        $transcript = new JsonStringTap('transcript');
        $emotion = new JsonStringTap('emotion');
        $reply = new JsonStringTap('reply');
        $listening = $input->kind === TurnKind::Audio;
        $buffer = '';

        try {
            $raw = $this->ai->streamJson(
                $this->requests->make($conversation, $input, $language),
                function (string $piece) use (&$buffer, &$listening, $transcript, $emotion, $reply, $emit, $line): void {
                    $buffer .= $piece;

                    if ($listening) {
                        $transcript->feed($buffer);
                        if ($transcript->closed()) {
                            $listening = false;
                            $emit('transcript', ['text' => trim($transcript->value())]);
                        }
                    }

                    if ($line->announced()) {
                        return;
                    }

                    $emotion->feed($buffer);
                    $delta = $reply->feed($buffer);
                    if ($delta !== '') {
                        $emit('delta', ['text' => $delta]);
                    }

                    if ($reply->closed() && trim($reply->value()) !== '') {
                        $line->announce(trim($reply->value()), $emotion->closed() ? $emotion->value() : null);
                        $line->voiceNext();
                    }
                },
            );
            $result = TurnResult::from($raw);
        } catch (JsonException) {
            // The reply was readable but the rest of the object broke: keep the turn, minus the mistakes.
            $result = new TurnResult(trim($transcript->value()), trim($reply->value()), $emotion->value() ?: null, []);
        }

        if ($result->reply === '') {
            throw new AiRejected('Empty reply');
        }
        if (! $line->announced()) {
            $line->announce($result->reply, $result->emotion);
        }

        $whole = $line->wholeClip();
        $turn = $this->history->append($conversation, $input, $result, $whole?->url);
        $release();
        $emit('saved', ['id' => $turn->id]);

        $this->finish($line, $turn, keepVoice: $whole === null, emit: $emit);
    }

    /**
     * Sends a turn that was already answered once more, the same events in the same order, when the app
     * retries it because the first answer never reached it. Nothing is asked of the model again, and the
     * voice comes from the speech cache.
     *
     * @param  Closure(string, array<string, mixed>): void  $emit
     */
    public function replay(Conversation $conversation, Turn $tutor, TurnInput $input, bool $clips, Closure $emit): void
    {
        if ($input->kind === TurnKind::Audio) {
            $said = $this->history->learnerLineBefore($tutor);
            if ($said !== '') {
                $emit('transcript', ['text' => $said]);
            }
        }

        $line = new SpokenLine($this->speech, $this->persona->voice($conversation), $clips, $emit);
        $line->announce($tutor->text, $tutor->emotion);
        $emit('saved', ['id' => $tutor->id]);

        $this->finish($line, $tutor, keepVoice: $tutor->audio_url === null, emit: $emit);
    }

    /**
     * Voices what is left of the line, keeps a one-piece voice on the turn for the history, and ends the stream.
     *
     * @param  Closure(string, array<string, mixed>): void  $emit
     */
    private function finish(SpokenLine $line, Turn $turn, bool $keepVoice, Closure $emit): void
    {
        while ($line->hasUnvoicedPieces()) {
            // The app gave up on this turn (a new one was sent): don't spend TTS quota on it.
            if (connection_aborted()) {
                return;
            }
            $line->voiceNext();
        }

        if ($keepVoice && ($clip = $line->wholeClip())) {
            $turn->update(['audio_url' => $clip->url]);
        }

        $emit('done', ['id' => $turn->id]);
    }
}
