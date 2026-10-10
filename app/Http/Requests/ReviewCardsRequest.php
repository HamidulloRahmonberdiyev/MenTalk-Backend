<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ReviewCardsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1', 'max:30'],
            'answers.*.word' => ['required', 'string', 'max:64'],
            'answers.*.correct' => ['required', 'boolean'],
            'answers.*.answered_at' => ['required', 'date'],
        ];
    }
}
