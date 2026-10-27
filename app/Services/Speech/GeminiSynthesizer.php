<?php

declare(strict_types=1);

namespace App\Services\Speech;

use App\Contracts\SpeechSynthesizer;
use App\DTO\SpeechClip;
use App\Exceptions\AiRejected;
use App\Services\Ai\GeminiTransport;
use Illuminate\Support\Facades\Storage;

final readonly class GeminiSynthesizer implements SpeechSynthesizer
{
    /** Natural Russian runs at ~70 ms per character; well under a third of that is not speech. */
    private const MIN_MS_PER_CHAR = 20;

    public function __construct(private GeminiTransport $transport, private string $model) {}

    public function speak(string $text, string $voice): SpeechClip
    {
        $response = $this->transport->generate($this->model, [
            'contents' => [['parts' => [['text' => $text]]]],
            'generationConfig' => [
                'responseModalities' => ['AUDIO'],
                'speechConfig' => ['voiceConfig' => ['prebuiltVoiceConfig' => ['voiceName' => $voice]]],
            ],
        ]);

        $inline = collect($response['candidates'][0]['content']['parts'] ?? [])->firstWhere('inlineData')['inlineData']
            ?? throw new AiRejected('No audio in response');
        $pcm = Wav::pcm(base64_decode($inline['data'], true) ?: '') ?: throw new AiRejected('Invalid audio payload');
        $durationMs = intdiv(strlen($pcm) * 1000, 48_000);

        // The model now and then returns a garbled blip instead of the line; never cache or play that.
        if ($durationMs < mb_strlen($text) * self::MIN_MS_PER_CHAR) {
            throw new AiRejected("Speech too short: {$durationMs} ms for ".mb_strlen($text).' chars');
        }

        $path = hash('sha256', "{$voice}|{$text}").'.wav';

        Storage::disk('speech')->put($path, Wav::fromPcm($pcm));

        return new SpeechClip(Storage::disk('speech')->url($path), $durationMs);
    }
}
