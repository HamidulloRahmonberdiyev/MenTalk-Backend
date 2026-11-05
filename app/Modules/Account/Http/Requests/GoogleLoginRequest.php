<?php

declare(strict_types=1);

namespace App\Modules\Account\Http\Requests;

use App\Modules\Language\Enums\InterfaceLanguage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class GoogleLoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id_token' => ['required', 'string', 'max:4096'],
            'device' => ['required', 'string', 'max:60'],
            // The phone's language, used only when this login creates the profile.
            'ui_language' => ['sometimes', Rule::enum(InterfaceLanguage::class)],
        ];
    }
}
