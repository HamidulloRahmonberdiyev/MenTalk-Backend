<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TranslateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:500'],
            'language' => ['required', Rule::in(['uz', 'ru', 'en'])],
        ];
    }
}
