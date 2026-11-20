<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Services;

use App\Modules\Speech\Contracts\SpeechSynthesizer;
use App\Modules\Speech\DTO\SpeechClip;
use App\Modules\Speech\Services\SpeechChunker;
use Closure;
use Throwable;

/**
 * One tutor line on its way out of a turn stream: announced once as `reply`, then voiced piece by piece,
 * each piece sent as an `audio` event the moment it is ready.
 *
 * With `$clips` the voice comes as up to two pieces (`text` and `last` on every `audio`; `url` null for
 * a piece that failed). Without it, older apps get one clip for the whole reply, or no `audio` at all
 * when that failed.
 */
final class SpokenLine
{
    /** @var list<string>|null */
    private ?array $pieces = null;

    /** @var list<SpeechClip|null> */
    private array $voiced = [];

    /** @param  Closure(string, array<string, mixed>): void  $emit */
    public function __construct(
        private readonly SpeechSynthesizer $speech,
        private readonly string $voice,
        private readonly bool $clips,
        private readonly Closure $emit,
    ) {}

    public function announced(): bool
    {
        return $this->pieces !== null;
    }

    public function announce(string $text, ?string $mood): void
    {
        ($this->emit)('reply', ['id' => null, 'reply' => $text, 'emotion' => $mood ?: 'neutral']);
        $this->pieces = $this->clips ? SpeechChunker::split($text) : [$text];
    }

    public function hasUnvoicedPieces(): bool
    {
        return $this->pieces !== null && count($this->voiced) < count($this->pieces);
    }

    /** Voices the next piece and sends it. */
    public function voiceNext(): void
    {
        $index = count($this->voiced);
        $text = $this->pieces[$index];
        $this->voiced[] = $clip = $this->speak($text);
        $last = $index === count($this->pieces) - 1;

        if ($this->clips) {
            ($this->emit)('audio', ['url' => $clip?->url, 'ms' => $clip?->durationMs, 'text' => $text, 'last' => $last]);
        } elseif ($clip) {
            ($this->emit)('audio', ['url' => $clip->url, 'ms' => $clip->durationMs]);
        }
    }

    /** The clip of the whole line, when it is said in one piece and that piece has been voiced. */
    public function wholeClip(): ?SpeechClip
    {
        return $this->pieces !== null && count($this->pieces) === 1 ? ($this->voiced[0] ?? null) : null;
    }

    /** Voice is a nice-to-have: without it the reply still goes out, and the app asks for it again. */
    private function speak(string $text): ?SpeechClip
    {
        try {
            return $this->speech->speak($text, $this->voice);
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }
}
