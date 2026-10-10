<?php

declare(strict_types=1);

namespace App\Services\Ai\Schemas;

final class EvaluationSchema
{
    public static function get(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'speech' => ['type' => 'NUMBER'],
                'vocabulary' => ['type' => 'NUMBER'],
                'grammar' => ['type' => 'NUMBER'],
                'mistakes' => ['type' => 'ARRAY', 'items' => MistakeSchema::get()],
                'newWords' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'word' => ['type' => 'STRING'],
                            'translation' => ['type' => 'STRING'],
                        ],
                        'required' => ['word', 'translation'],
                    ],
                ],
            ],
            'required' => ['speech', 'vocabulary', 'grammar', 'mistakes', 'newWords'],
        ];
    }
}
