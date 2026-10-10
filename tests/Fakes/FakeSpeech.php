<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Contracts\SpeechSynthesizer;
use App\DTO\SpeechClip;

final class FakeSpeech implements SpeechSynthesizer
{
    public function speak(string $text, string $voice): SpeechClip
    {
        return new SpeechClip('https://cdn.test/a.wav', 1200);
    }
}
