<?php

declare(strict_types=1);

namespace App\Modules\Translator\Schemas;

use App\Modules\Translator\Support\TranslatorLanguages;

/** The JSON the AI answers a translation with. camelCase: it is the model's contract, not the API's. */
final class TranslationSchema
{
    public const REGISTERS = ['neutral', 'formal', 'informal', 'slang'];

    public static function get(): array
    {
        $codes = TranslatorLanguages::codes();

        return [
            'type' => 'OBJECT',
            'properties' => [
                'sourceLanguage' => ['type' => 'STRING', 'enum' => $codes, 'description' => 'Language of the input'],
                'targetLanguage' => ['type' => 'STRING', 'enum' => $codes, 'description' => 'Language the translation is in'],
                'translation' => ['type' => 'STRING'],
                'reading' => ['type' => 'STRING', 'description' => 'Latin-letter reading of the translation if its script is not Latin; otherwise empty'],
                'stressed' => ['type' => 'STRING', 'description' => 'Russian translation with a combining acute accent on stressed vowels; empty if the translation is not Russian'],
                'correctedInput' => ['type' => 'STRING', 'description' => 'Input with obvious typos fixed; empty if nothing needed fixing'],
                'meanings' => [
                    'type' => 'ARRAY',
                    'description' => 'Only for a single word or very short phrase with several distinct senses: up to 4, most common first. Otherwise empty',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'translation' => ['type' => 'STRING'],
                            'partOfSpeech' => ['type' => 'STRING', 'description' => 'Short, in the UI language, e.g. noun'],
                            'exampleSource' => ['type' => 'STRING', 'description' => 'A short example sentence in the source language'],
                            'exampleTarget' => ['type' => 'STRING', 'description' => 'The same example in the target language'],
                        ],
                        'required' => ['translation', 'partOfSpeech'],
                    ],
                ],
                'alternatives' => [
                    'type' => 'ARRAY',
                    'description' => 'Up to 3 genuinely different natural ways to say it in the target language; empty if there is only one sensible way',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'text' => ['type' => 'STRING'],
                            'register' => ['type' => 'STRING', 'enum' => self::REGISTERS],
                        ],
                        'required' => ['text', 'register'],
                    ],
                ],
                'note' => ['type' => 'STRING', 'description' => 'One short usage tip in the UI language (ambiguity, gender, formality); empty when not needed'],
            ],
            'required' => ['sourceLanguage', 'targetLanguage', 'translation', 'reading', 'stressed', 'correctedInput', 'meanings', 'alternatives', 'note'],
        ];
    }
}
