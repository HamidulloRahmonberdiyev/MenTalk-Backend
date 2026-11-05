<?php

declare(strict_types=1);

namespace App\Modules\Notification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RegisterPushTokenRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:255', 'regex:/^Expo(nent)?PushToken\[[^\]]+\]$/'],
            'platform' => ['required', Rule::in(['android', 'ios'])],
        ];
    }
}
