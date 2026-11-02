<?php

declare(strict_types=1);

namespace App\DTO;

/** A word a learner wants on their list, as it reaches the server. */
final readonly class NewWord
{
    public function __construct(
        public string $language,
        public string $text,
        /** The meaning, in `translationLanguage`; null when the learner saved the word without one. */
        public ?string $translation = null,
        public ?string $translationLanguage = null,
        public ?string $contextSentence = null,
        public ?string $form = null,
        public string $pos = '',
    ) {}
}
