<?php

declare(strict_types=1);

namespace App\Services\Ai\Schemas;

final class TurnSchema
{
    public static function get(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'transcript' => ['type' => 'STRING'],
                'reply' => ['type' => 'STRING'],
                'emotion' => ['type' => 'STRING', 'enum' => ['neutral', 'happy', 'encouraging', 'curious', 'empathetic', 'surprised', 'playful']],
                'mistakes' => ['type' => 'ARRAY', 'items' => MistakeSchema::get()],
            ],
            'required' => ['transcript', 'reply', 'emotion', 'mistakes'],
        ];
    }
}
