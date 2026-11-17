<?php

declare(strict_types=1);

namespace App\Modules\Speech\Services;

use App\Modules\Ai\Exceptions\AiOverloaded;
use App\Modules\Ai\Exceptions\AiRateLimited;
use App\Modules\Ai\Exceptions\AiRejected;
use App\Modules\Speech\Contracts\SpeechSynthesizer;
use App\Modules\Speech\DTO\SpeechClip;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Support\Sleep;
use Throwable;

/**
 * The tutor is one person with one voice. A line the server cannot voice is left silent by the app,
 * which never swaps in another voice, so a failed synthesis is tried again before anyone hears the gap:
 *
 *  - a rate limit, an overloaded model, a dropped connection or a garbled clip: the same voice again,
 *    on the next key (the pool skips the limited one) after a short pause;
 *  - the model refusing the request outright (retired, misconfigured): straight to the fallback model;
 *  - no key free at all: nothing to try within seconds, so it gives up at once.
 *
 * Prebuilt voices are shared by the Gemini TTS models, so the fallback model speaks with the same voice.
 * No new attempt starts once `DEADLINE_MS` has passed: the learner is waiting for this line.
 */
final readonly class ResilientSynthesizer implements SpeechSynthesizer
{
    /** Pauses before the second and third attempts; the first goes at once. */
    private const BACKOFF_MS = [250, 750];

    private const DEADLINE_MS = 8_000;

    public function __construct(private SpeechSynthesizer $primary, private ?SpeechSynthesizer $fallback = null) {}

    public function speak(string $text, string $voice): SpeechClip
    {
        $started = hrtime(true);
        $plan = $this->fallback ? [$this->primary, $this->primary, $this->fallback] : [$this->primary, $this->primary, $this->primary];

        for ($attempt = 0; ; $attempt++) {
            try {
                return $plan[$attempt]->speak($text, $voice);
            } catch (Throwable $e) {
                $next = $this->nextAttempt($e, $attempt, $plan);
                $pause = self::BACKOFF_MS[$next - 1] ?? 0;

                if ($next === null || $this->elapsedMs($started) + $pause >= self::DEADLINE_MS) {
                    throw $e;
                }

                Sleep::for($pause)->milliseconds();
                $attempt = $next - 1;
            }
        }
    }

    /**
     * Which attempt of `$plan` follows a failure of attempt `$attempt`, or null to give up.
     *
     * @param  list<SpeechSynthesizer>  $plan
     */
    private function nextAttempt(Throwable $e, int $attempt, array $plan): ?int
    {
        $last = count($plan) - 1;

        if ($attempt >= $last) {
            return null;
        }
        if ($this->passing($e)) {
            return $attempt + 1;
        }
        // The model itself turned the request down: the same model will again, a different one may not.
        if ($e instanceof AiRejected && $this->fallback) {
            return $last;
        }

        return null;
    }

    /** A failure that the same request may well not meet a moment later. */
    private function passing(Throwable $e): bool
    {
        return $e instanceof AiRateLimited
            || $e instanceof AiOverloaded
            || $e instanceof TransferException
            // No HTTP status: the model answered, but with no audio or a garbled blip.
            || ($e instanceof AiRejected && $e->getCode() === 0);
    }

    private function elapsedMs(int $started): int
    {
        return intdiv(hrtime(true) - $started, 1_000_000);
    }
}
