<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Services;

use App\Modules\Conversation\Models\Conversation;
use App\Modules\Language\Contracts\LanguageCatalog;
use App\Modules\Language\DTO\TutorLanguage;
use App\Modules\Speech\Enums\VoiceGender;
use App\Modules\Speech\Support\GeminiVoices;

/**
 * Who the tutor of a conversation is: the voice it speaks with, for every line and every retry of one,
 * and the person that voice belongs to. A male voice is a male tutor, with the language's male tutor
 * name in the prompts and masculine forms when he speaks about himself.
 */
final readonly class TutorPersona
{
    public function __construct(private LanguageCatalog $languages) {}

    /** The voice the learner picked when the conversation started, or the language's default voice. */
    public function voice(Conversation $conversation): string
    {
        return $conversation->voice ?? $this->defaultVoice($conversation->target_language);
    }

    /** The voice a conversation in `$language` gets when the learner picked none. */
    public function defaultVoice(string $language): string
    {
        return $this->languages->get($language)->voice ?? config('ai.voice');
    }

    public function gender(Conversation $conversation): VoiceGender
    {
        return GeminiVoices::gender($this->voice($conversation)) ?? VoiceGender::Female;
    }

    /** The conversation's language as its prompts should name the tutor. */
    public function language(Conversation $conversation): TutorLanguage
    {
        $language = $this->languages->get($conversation->target_language);

        return $this->gender($conversation) === VoiceGender::Male ? $language->withMaleTutor() : $language;
    }
}
