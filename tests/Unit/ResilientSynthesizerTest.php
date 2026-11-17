<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Ai\Exceptions\AiOverloaded;
use App\Modules\Ai\Exceptions\AiRateLimited;
use App\Modules\Ai\Exceptions\AiRejected;
use App\Modules\Ai\Exceptions\AiUnavailable;
use App\Modules\Speech\Contracts\SpeechSynthesizer;
use App\Modules\Speech\DTO\SpeechClip;
use App\Modules\Speech\Services\ResilientSynthesizer;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Sleep;
use LogicException;
use Tests\TestCase;
use Throwable;

final class ResilientSynthesizerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Sleep::fake();
    }

    /** A synthesizer that answers each call with the next outcome, and records the voices it was asked for. */
    private static function scripted(Throwable|SpeechClip ...$outcomes): SpeechSynthesizer
    {
        return new class($outcomes) implements SpeechSynthesizer
        {
            /** @var list<string> */
            public array $voices = [];

            public function __construct(private array $outcomes) {}

            public function speak(string $text, string $voice): SpeechClip
            {
                $this->voices[] = $voice;
                $outcome = $this->outcomes[count($this->voices) - 1] ?? throw new LogicException('Called once too often');

                return $outcome instanceof Throwable ? throw $outcome : $outcome;
            }
        };
    }

    private static function clip(): SpeechClip
    {
        return new SpeechClip('https://cdn.test/line.wav', 1500);
    }

    public function test_a_garbled_clip_is_voiced_again_with_the_same_voice(): void
    {
        $primary = self::scripted(new AiRejected('Speech too short'), self::clip());

        $clip = (new ResilientSynthesizer($primary))->speak('Привет!', 'Sulafat');

        $this->assertSame('https://cdn.test/line.wav', $clip->url);
        $this->assertSame(['Sulafat', 'Sulafat'], $primary->voices);
        Sleep::assertSequence([Sleep::for(250)->milliseconds()]);
    }

    public function test_rate_limits_overload_and_dropped_connections_are_retried(): void
    {
        $primary = self::scripted(
            new AiRateLimited,
            new ConnectException('timed out', new Request('POST', 'https://ai.test')),
            self::clip(),
        );

        (new ResilientSynthesizer($primary))->speak('Привет!', 'Sulafat');

        $this->assertCount(3, $primary->voices);
        Sleep::assertSequence([Sleep::for(250)->milliseconds(), Sleep::for(750)->milliseconds()]);
    }

    public function test_the_fallback_model_takes_the_last_attempt(): void
    {
        $primary = self::scripted(new AiOverloaded, new AiOverloaded);
        $fallback = self::scripted(self::clip());

        (new ResilientSynthesizer($primary, $fallback))->speak('Привет!', 'Sulafat');

        $this->assertCount(2, $primary->voices);
        $this->assertSame(['Sulafat'], $fallback->voices);
    }

    public function test_a_refused_request_goes_straight_to_the_fallback_model(): void
    {
        $primary = self::scripted(new AiRejected('404', 404));
        $fallback = self::scripted(self::clip());

        (new ResilientSynthesizer($primary, $fallback))->speak('Привет!', 'Sulafat');

        $this->assertCount(1, $primary->voices);
        $this->assertCount(1, $fallback->voices);
    }

    public function test_a_refused_request_is_not_repeated_without_a_fallback_model(): void
    {
        $primary = self::scripted(new AiRejected('400', 400));

        $this->expectException(AiRejected::class);

        try {
            (new ResilientSynthesizer($primary))->speak('Привет!', 'Sulafat');
        } finally {
            $this->assertCount(1, $primary->voices);
            Sleep::assertNeverSlept();
        }
    }

    public function test_it_gives_up_after_three_attempts(): void
    {
        $primary = self::scripted(new AiOverloaded, new AiOverloaded, new AiOverloaded);

        $this->expectException(AiOverloaded::class);

        try {
            (new ResilientSynthesizer($primary))->speak('Привет!', 'Sulafat');
        } finally {
            $this->assertCount(3, $primary->voices);
        }
    }

    public function test_it_does_not_wait_for_a_key_when_none_is_free(): void
    {
        $primary = self::scripted(new AiUnavailable);
        $fallback = self::scripted(self::clip());

        $this->expectException(AiUnavailable::class);

        try {
            (new ResilientSynthesizer($primary, $fallback))->speak('Привет!', 'Sulafat');
        } finally {
            $this->assertCount(1, $primary->voices);
            $this->assertSame([], $fallback->voices);
        }
    }
}
