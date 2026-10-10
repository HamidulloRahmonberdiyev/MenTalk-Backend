<?php

declare(strict_types=1);

namespace App\Services\Speech;

final class Wav
{
    public static function fromPcm(string $pcm, int $rate = 24_000): string
    {
        $size = strlen($pcm);

        return 'RIFF'.pack('V', 36 + $size).'WAVEfmt '
            .pack('VvvVVvv', 16, 1, 1, $rate, $rate * 2, 2, 16)
            .'data'.pack('V', $size).$pcm;
    }
}
