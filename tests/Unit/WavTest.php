<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Speech\Wav;
use PHPUnit\Framework\TestCase;

final class WavTest extends TestCase
{
    public function test_bare_pcm_is_kept_as_is(): void
    {
        $this->assertSame("\x01\x00\x02\x00", Wav::pcm("\x01\x00\x02\x00"));
    }

    public function test_wav_payload_yields_only_its_samples(): void
    {
        $pcm = str_repeat("\x10\x00", 50);
        $signature = 'C2PA'.pack('V', 5).'proof'."\x00";
        $wav = Wav::fromPcm($pcm).$signature;

        $this->assertSame($pcm, Wav::pcm($wav));
    }

    public function test_wav_without_data_chunk_is_empty(): void
    {
        $this->assertSame('', Wav::pcm('RIFF'.pack('V', 4).'WAVE'));
    }
}
