<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Contracts\LanguageCatalog;
use App\Rules\TaughtLanguage;
use Illuminate\Foundation\Http\FormRequest;

final class ReviewCardsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1', 'max:30'],
            'answers.*.word' => ['required', 'string', 'max:64'],
            'answers.*.language' => ['nullable', 'string', TaughtLanguage::make(includeSwitchedOff: true)],
            'answers.*.correct' => ['required', 'boolean'],
            'answers.*.answered_at' => ['required', 'date'],
        ];
    }

    /** @return list<array{word: string, language: string, correct: bool, answered_at: string}> */
    public function answers(LanguageCatalog $languages): array
    {
        $default = $languages->default()->code;

        return array_map(
            fn (array $answer) => ['language' => $answer['language'] ?? $default] + $answer,
            $this->validated('answers'),
        );
    }
}
