<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Actions;

use App\Modules\Ai\Contracts\AiClient;
use App\Modules\Ai\Exceptions\AiRejected;
use App\Modules\Conversation\DTO\TurnInput;
use App\Modules\Conversation\DTO\TurnResult;
use App\Modules\Conversation\Enums\TurnKind;
use App\Modules\Conversation\Models\Conversation;
use App\Modules\Conversation\Services\JsonStringTap;
use App\Modules\Conversation\Services\TurnHistory;
use App\Modules\Conversation\Services\TurnRequests;
use App\Modules\Language\Contracts\LanguageCatalog;
use App\Modules\Speech\Contracts\SpeechSynthesizer;
use App\Modules\Speech\DTO\SpeechClip;
use App\Modules\Speech\Services\SpeechChunker;
use Closure;
use JsonException;
use Throwable;

/**
 * One learner turn, answered as events while it is produced (see `POST .../turns/stream`):
 *
 * - `transcript` as soon as the model has written down what the learner said (voice turns);
 * - `delta`… the reply word by word, then `reply` with the finished text;
 * - `audio` for the voice. The first piece is synthesized the moment the reply text is complete, while
 *   the model is still writing up the mistakes, so the voice is ready about when the text is;
 * - `done` once the turn is saved.
 *
 * With `$clips` the voice comes as up to two pieces (`text` and `last` on every `audio`; `url` null for
 * a piece that failed, which the app then reads with the device voice). Without it, older apps get one
 * clip for the whole reply, or no `audio` at all when that failed.
 */
final readonly class StreamTurn
{
    public function __construct(
        private AiClient $ai,
        private TurnRequests $requests,
        private SpeechSynthesizer $speech,
        private TurnHistory $history,
        private LanguageCatalog $languages,
    ) {}

    /**
     * @param  Closure(string, array<string, mixed>): void  $emit
     * @param  Closure(): void  $release  frees the conversation for the next turn; voicing needs no lock
     */
    public function __invoke(Conversation $conversation, TurnInput $input, bool $clips, Closure $emit, Closure $release): void
    {
        $conversation->loadMissing(['scenario', 'user.profile']);
        $language = $this->languages->get($conversation->target_language);
        $voice = $language->voice ?? config('ai.voice');

        $transcript = new JsonStringTap('transcript');
        $emotion = new JsonStringTap('emotion');
        $reply = new JsonStringTap('reply');
        $listening = $input->kind === TurnKind::Audio;
        $buffer = '';
        /** @var list<string>|null $pieces */
        $pieces = null;
        /** @var list<SpeechClip|null> $voiced */
        $voiced = [];

        $announce = function (string $text, ?string $mood) use (&$pieces, $clips, $emit): void {
            $emit('reply', ['id' => null, 'reply' => $text, 'emotion' => $mood ?: 'neutral']);
            $pieces = $clips ? SpeechChunker::split($text) : [$text];
        };
        $voiceNext = function () use (&$pieces, &$voiced, $clips, $emit, $voice): void {
            $index = count($voiced);
            $text = $pieces[$index];
            $voiced[] = $clip = $this->speak($text, $voice);
            $last = $index === count($pieces) - 1;

            if ($clips) {
                $emit('audio', ['url' => $clip?->url, 'ms' => $clip?->durationMs, 'text' => $text, 'last' => $last]);
            } elseif ($clip) {
                $emit('audio', ['url' => $clip->url, 'ms' => $clip->durationMs]);
            }
        };

        try {
            $raw = $this->ai->streamJson(
                $this->requests->make($conversation, $input, $language),
                function (string $piece) use (&$buffer, &$pieces, &$listening, $transcript, $emotion, $reply, $emit, $announce, $voiceNext): void {
                    $buffer .= $piece;

                    if ($listening) {
                        $transcript->feed($buffer);
                        if ($transcript->closed()) {
                            $listening = false;
                            $emit('transcript', ['text' => trim($transcript->value())]);
                        }
                    }

                    if ($pieces !== null) {
                        return;
                    }

                    $emotion->feed($buffer);
                    $delta = $reply->feed($buffer);
                    if ($delta !== '') {
                        $emit('delta', ['text' => $delta]);
                    }

                    if ($reply->closed() && trim($reply->value()) !== '') {
                        $announce(trim($reply->value()), $emotion->closed() ? $emotion->value() : null);
                        $voiceNext();
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
        if ($pieces === null) {
            $announce($result->reply, $result->emotion);
        }

        $whole = count($pieces) === 1 ? ($voiced[0] ?? null) : null;
        $turn = $this->history->append($conversation, $input, $result, $whole?->url);
        $release();

        while (count($voiced) < count($pieces)) {
            // The app gave up on this turn (a new one was sent): don't spend TTS quota on it.
            if (connection_aborted()) {
                return;
            }
            $voiceNext();
        }

        if (! $whole && count($pieces) === 1 && $voiced[0]) {
            $turn->update(['audio_url' => $voiced[0]->url]);
        }

        $emit('done', ['id' => $turn->id]);
    }

    /** Voice is a nice-to-have: without it the reply still goes out and the app reads it with the device voice. */
    private function speak(string $text, string $voice): ?SpeechClip
    {
        try {
            return $this->speech->speak($text, $voice);
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }
}
