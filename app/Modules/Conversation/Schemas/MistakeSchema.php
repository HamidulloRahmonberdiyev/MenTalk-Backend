<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Schemas;

use App\Modules\Conversation\Enums\MistakeCategory;

final class MistakeSchema
{
    public static function get(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'said' => ['type' => 'STRING', 'description' => 'The learner\'s words exactly as said, only the part with the mistake and a little context'],
                'correct' => ['type' => 'STRING', 'description' => 'The same words corrected, changing nothing else'],
                'note' => ['type' => 'STRING'],
                'category' => [
                    'type' => 'STRING',
                    'enum' => MistakeCategory::values(),
                    'description' => 'verb_form: tense, aspect, conjugation; case_ending: noun or adjective case; agreement: gender, number, person; word_order; preposition; article; pronoun; word_choice: wrong or unnatural word; spelling: also wrong letters heard in speech; other',
                ],
            ],
            'required' => ['said', 'correct', 'note', 'category'],
        ];
    }
}
