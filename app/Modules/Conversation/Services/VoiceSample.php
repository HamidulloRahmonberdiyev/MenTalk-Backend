<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Services;

use App\Modules\Conversation\Models\TutorVoice;
use App\Modules\Language\Contracts\LanguageCatalog;
use App\Modules\Speech\Contracts\SpeechSynthesizer;
use App\Modules\Speech\DTO\SpeechClip;
use App\Modules\Speech\Enums\VoiceGender;

/**
 * What a tutor voice sounds like in the language the learner studies: the tutor greeting them with the
 * name that voice goes by. The text never changes, so the speech cache answers every listen after the first.
 */
final readonly class VoiceSample
{
    /** Per language: with the tutor's name, and without it for a male voice the language has no name for. */
    private const LINES = [
        'ru' => ['Привет! Меня зовут {name}. Давай немного поговорим по-русски.', 'Привет! Давай немного поговорим по-русски.'],
        'en' => ["Hi! I'm {name}. Let's have a little chat in English.", "Hi! Let's have a little chat in English."],
        'uz' => ['Salom! Mening ismim {name}. Keling, biroz suhbatlashamiz.', 'Salom! Keling, biroz suhbatlashamiz.'],
        'tr' => ['Merhaba! Benim adım {name}. Biraz sohbet edelim mi?', 'Merhaba! Biraz sohbet edelim mi?'],
        'de' => ['Hallo! Ich heiße {name}. Lass uns ein bisschen plaudern.', 'Hallo! Lass uns ein bisschen plaudern.'],
        'kk' => ['Сәлем! Менің атым {name}. Аздап сөйлесейік.', 'Сәлем! Аздап сөйлесейік.'],
        'ko' => ['안녕하세요! 저는 {name}입니다. 같이 이야기해 봐요.', '안녕하세요! 같이 이야기해 봐요.'],
        'ar' => ['مرحبًا! اسمي {name}. هيا نتحدث قليلًا.', 'مرحبًا! هيا نتحدث قليلًا.'],
    ];

    public function __construct(private LanguageCatalog $languages, private SpeechSynthesizer $speech) {}

    public function __invoke(TutorVoice $voice, string $language): SpeechClip
    {
        return $this->speech->speak($this->text($voice, $language), $voice->voice);
    }

    public function text(TutorVoice $voice, string $language): string
    {
        $tutor = $this->languages->get($language);
        $name = $voice->gender === VoiceGender::Male ? $tutor->maleTutorName : $tutor->tutorName;
        [$named, $plain] = self::LINES[$tutor->code] ?? self::LINES['en'];

        return $name === null ? $plain : str_replace('{name}', $name, $named);
    }
}
