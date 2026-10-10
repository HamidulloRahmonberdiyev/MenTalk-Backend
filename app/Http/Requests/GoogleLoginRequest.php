<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GoogleLoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id_token' => ['required', 'string', 'max:4096'],
            'device' => ['required', 'string', 'max:60'],
        ];
    }
}
