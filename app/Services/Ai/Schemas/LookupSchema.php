<?php

declare(strict_types=1);

namespace App\Services\Ai\Schemas;

final class LookupSchema
{
    public static function get(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'form' => ['type' => 'STRING'],
                'translation' => ['type' => 'STRING'],
            ],
            'required' => ['form', 'translation'],
        ];
    }
}
