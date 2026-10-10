<?php

declare(strict_types=1);

namespace App\Services\Speech;

use App\Contracts\SpeechSynthesizer;
use App\DTO\SpeechClip;
use App\Support\Text;
use Illuminate\Support\Facades\Cache;

final readonly class CachedSynthesizer implements SpeechSynthesizer
{
    private const TTL_DAYS = 30;

    public function __construct(private SpeechSynthesizer $inner) {}

    public function speak(string $text, string $voice): SpeechClip
    {
        $text = Text::withoutEmoji($text);
        $key = 'speech:'.hash('sha256', "{$voice}|{$text}");

        return Cache::get($key) ?? Cache::lock("{$key}:lock", 20)->block(15, function () use ($key, $text, $voice) {
            return Cache::get($key) ?? tap(
                $this->inner->speak($text, $voice),
                fn (SpeechClip $clip) => Cache::put($key, $clip, now()->addDays(self::TTL_DAYS)),
            );
        });
    }
}
