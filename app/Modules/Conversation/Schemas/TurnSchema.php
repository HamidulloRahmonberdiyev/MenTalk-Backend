<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Schemas;

final class TurnSchema
{
    public static function get(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'transcript' => ['type' => 'STRING', 'description' => 'What the learner said, word for word, mistakes included'],
                'reply' => [
                    'type' => 'STRING',
                    'description' => 'What the tutor says aloud: when the learner made a real mistake, first one short sentence in their interface language correcting the main one; then the role-play line in the taught language, ending with a question',
                ],
                // No new values here: apps already out there only know these seven.
                'emotion' => [
                    'type' => 'STRING',
                    'enum' => ['neutral', 'happy', 'encouraging', 'curious', 'empathetic', 'surprised', 'playful'],
                    'description' => 'playful: jokes and teasing; surprised: mock outrage or disbelief; happy or encouraging: praise; empathetic: the learner struggles',
                ],
                'mistakes' => ['type' => 'ARRAY', 'items' => MistakeSchema::get()],
            ],
            'required' => ['transcript', 'reply', 'emotion', 'mistakes'],
            // Streamed in this order: the reply can be shown and voiced while the mistakes are still being written.
            'propertyOrdering' => ['transcript', 'emotion', 'reply', 'mistakes'],
        ];
    }
}
