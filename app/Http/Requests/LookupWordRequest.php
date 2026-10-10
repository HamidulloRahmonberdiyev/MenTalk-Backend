<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class LookupWordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'word' => ['required', 'string', 'max:64'],
            'sentence' => ['required', 'string', 'max:500'],
            'language' => ['required', Rule::in(['uz', 'ru', 'en'])],
        ];
    }
}
