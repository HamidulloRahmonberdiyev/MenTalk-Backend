<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\DTO\TutorLanguage;

/**
 * Turns a prompt template into text for one taught language. Prompts never name a language or a
 * tutor themselves; they use these placeholders, so a new language needs no new prompts:
 * {language} {tutor} {script} {follow_ups} {language_rules} {basic_words} {dictionary_form}.
 */
final readonly class PromptRenderer
{
    public const LANGUAGE_PLACEHOLDERS = [
        '{language}', '{tutor}', '{script}', '{follow_ups}', '{language_rules}', '{basic_words}', '{dictionary_form}',
    ];

    public function __construct(private PromptStore $prompts) {}

    /** The prompt stored under `$key` (or its `key@language` variant), filled in. */
    public function render(string $key, TutorLanguage $language, array $values = []): string
    {
        return $this->fill($this->prompts->get($key, $language->code), $language, $values);
    }

    /** `$values` are inserted as they are: placeholders inside them are not expanded again. */
    public function fill(string $template, TutorLanguage $language, array $values = []): string
    {
        return strtr($template, [
            '{language}' => $language->name,
            '{tutor}' => $language->tutorName,
            '{script}' => $language->scriptNote,
            '{follow_ups}' => $language->followUps,
            '{language_rules}' => $language->rules,
            '{basic_words}' => $language->basicWords,
            '{dictionary_form}' => $language->dictionaryForm,
            ...$values,
        ]);
    }
}
