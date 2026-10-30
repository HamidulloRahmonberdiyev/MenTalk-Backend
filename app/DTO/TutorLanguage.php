<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * A language the app teaches, with everything prompts need to speak it. Plain data: adding a
 * language is a row in `languages`, never a new class.
 */
final readonly class TutorLanguage
{
    public function __construct(
        public string $code,
        /** English name, as used inside prompts ("Russian"). */
        public string $name,
        public string $tutorName,
        /** TTS voice; null uses the default voice from `config('ai.voice')`. */
        public ?string $voice,
        /** Added after the name where the script matters (" (Cyrillic)"). */
        public string $scriptNote,
        /** Example closing questions, quoted for the prompt. */
        public string $followUps,
        /** Rules that only make sense for this language, as prompt lines. */
        public string $rules,
        /** Words too basic to suggest for learning. */
        public string $basicWords,
        /** What "dictionary form" means for this language. */
        public string $dictionaryForm,
        public bool $active,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            code: $row['code'],
            name: $row['name'],
            tutorName: $row['tutor_name'],
            voice: $row['voice'] ?? null,
            scriptNote: $row['script_note'] ?? '',
            followUps: $row['follow_ups'],
            rules: $row['rules'] ?? '',
            basicWords: $row['basic_words'],
            dictionaryForm: $row['dictionary_form'],
            active: (bool) $row['active'],
        );
    }
}
