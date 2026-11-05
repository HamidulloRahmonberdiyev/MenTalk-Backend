<?php

declare(strict_types=1);

namespace App\Modules\Speech\Contracts;

use App\Modules\Speech\DTO\SpeechClip;

interface SpeechSynthesizer
{
    public function speak(string $text, string $voice): SpeechClip;
}
