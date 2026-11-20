<?php

declare(strict_types=1);

namespace App\Modules\Speech\Support;

use App\Modules\Speech\Enums\VoiceGender;

/**
 * Gemini's prebuilt speech voices. Genders as Google lists the same voices for Cloud TTS
 * (https://docs.cloud.google.com/text-to-speech/docs/chirp3-hd); styles as the Gemini speech
 * generation docs describe them.
 */
final class GeminiVoices
{
    /** @var array<string, array{VoiceGender, string}> */
    private const VOICES = [
        'Achernar' => [VoiceGender::Female, 'Soft'],
        'Achird' => [VoiceGender::Male, 'Friendly'],
        'Algenib' => [VoiceGender::Male, 'Gravelly'],
        'Algieba' => [VoiceGender::Male, 'Smooth'],
        'Alnilam' => [VoiceGender::Male, 'Firm'],
        'Aoede' => [VoiceGender::Female, 'Breezy'],
        'Autonoe' => [VoiceGender::Female, 'Bright'],
        'Callirrhoe' => [VoiceGender::Female, 'Easy-going'],
        'Charon' => [VoiceGender::Male, 'Informative'],
        'Despina' => [VoiceGender::Female, 'Smooth'],
        'Enceladus' => [VoiceGender::Male, 'Breathy'],
        'Erinome' => [VoiceGender::Female, 'Clear'],
        'Fenrir' => [VoiceGender::Male, 'Excitable'],
        'Gacrux' => [VoiceGender::Female, 'Mature'],
        'Iapetus' => [VoiceGender::Male, 'Clear'],
        'Kore' => [VoiceGender::Female, 'Firm'],
        'Laomedeia' => [VoiceGender::Female, 'Upbeat'],
        'Leda' => [VoiceGender::Female, 'Youthful'],
        'Orus' => [VoiceGender::Male, 'Firm'],
        'Pulcherrima' => [VoiceGender::Female, 'Forward'],
        'Puck' => [VoiceGender::Male, 'Upbeat'],
        'Rasalgethi' => [VoiceGender::Male, 'Informative'],
        'Sadachbia' => [VoiceGender::Male, 'Lively'],
        'Sadaltager' => [VoiceGender::Male, 'Knowledgeable'],
        'Schedar' => [VoiceGender::Male, 'Even'],
        'Sulafat' => [VoiceGender::Female, 'Warm'],
        'Umbriel' => [VoiceGender::Male, 'Easy-going'],
        'Vindemiatrix' => [VoiceGender::Female, 'Gentle'],
        'Zephyr' => [VoiceGender::Female, 'Bright'],
        'Zubenelgenubi' => [VoiceGender::Male, 'Casual'],
    ];

    /** Null for a name this list does not know (a voice added after it, or a typo). */
    public static function gender(string $voice): ?VoiceGender
    {
        return (self::VOICES[$voice] ?? null)[0] ?? null;
    }

    /**
     * For a select in the admin panel: "Sulafat — Ayol, Warm".
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::VOICES as $name => [$gender, $style]) {
            $options[$name] = "{$name} — {$gender->label()}, {$style}";
        }

        return $options;
    }
}
