<?php

declare(strict_types=1);

namespace App\Services\Ai\Schemas;

final class MistakeSchema
{
    public static function get(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'said' => ['type' => 'STRING'],
                'correct' => ['type' => 'STRING'],
                'note' => ['type' => 'STRING'],
            ],
            'required' => ['said', 'correct', 'note'],
        ];
    }
}
