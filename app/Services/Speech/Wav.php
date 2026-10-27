<?php

declare(strict_types=1);

namespace App\Services\Speech;

final class Wav
{
    /**
     * The raw samples of a TTS payload. Gemini returns either bare PCM or a whole WAV file, and the WAV
     * carries extra chunks after the samples (a C2PA signature) that would play as noise if kept.
     */
    public static function pcm(string $audio): string
    {
        if (! str_starts_with($audio, 'RIFF') || substr($audio, 8, 4) !== 'WAVE') {
            return $audio;
        }

        for ($offset = 12; $offset + 8 <= strlen($audio); $offset += 8 + $size + ($size % 2)) {
            $size = unpack('V', substr($audio, $offset + 4, 4))[1];

            if (substr($audio, $offset, 4) === 'data') {
                return substr($audio, $offset + 8, $size);
            }
        }

        return '';
    }

    public static function fromPcm(string $pcm, int $rate = 24_000): string
    {
        $size = strlen($pcm);

        return 'RIFF'.pack('V', 36 + $size).'WAVEfmt '
            .pack('VvvVVvv', 16, 1, 1, $rate, $rate * 2, 2, 16)
            .'data'.pack('V', $size).$pcm;
    }
}
