<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Enums;

/**
 * The kind of a learner's mistake, as the tutor labels it. Language neutral on purpose: a case
 * ending in Russian and an article in English are both grammar, but drilling them is different.
 */
enum MistakeCategory: string
{
    case VerbForm = 'verb_form';
    case CaseEnding = 'case_ending';
    case Agreement = 'agreement';
    case WordOrder = 'word_order';
    case Preposition = 'preposition';
    case Article = 'article';
    case Pronoun = 'pronoun';
    case WordChoice = 'word_choice';
    case Spelling = 'spelling';
    case Other = 'other';

    /** Whatever the model sent, never an error: an unknown label is just "other". */
    public static function parse(mixed $value): self
    {
        return is_string($value) ? (self::tryFrom(strtolower(trim($value))) ?? self::Other) : self::Other;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $category) => $category->value, self::cases());
    }
}
