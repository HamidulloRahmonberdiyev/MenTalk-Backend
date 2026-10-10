<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\SpeechClip;

interface SpeechSynthesizer
{
    public function speak(string $text, string $voice): SpeechClip;
}
