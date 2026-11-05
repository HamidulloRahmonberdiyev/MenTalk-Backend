<?php

declare(strict_types=1);

namespace App\Modules\Speech\Services;

use App\Modules\Speech\Contracts\SpeechSynthesizer;
use App\Modules\Speech\DTO\SpeechClip;
use App\Shared\Support\Text;
use Illuminate\Support\Facades\Cache;

final readonly class CachedSynthesizer implements SpeechSynthesizer
{
    private const TTL_DAYS = 30;

    public function __construct(private SpeechSynthesizer $inner) {}

    public function speak(string $text, string $voice): SpeechClip
    {
        $text = Text::withoutEmoji($text);
        $key = 'speech:v2:'.hash('sha256', "{$voice}|{$text}");

        return SpeechClip::fromArray(Cache::get($key) ?? Cache::lock("{$key}:lock", 20)->block(15, function () use ($key, $text, $voice) {
            return Cache::get($key) ?? tap(
                $this->inner->speak($text, $voice)->toArray(),
                fn (array $clip) => Cache::put($key, $clip, now()->addDays(self::TTL_DAYS)),
            );
        }));
    }
}
