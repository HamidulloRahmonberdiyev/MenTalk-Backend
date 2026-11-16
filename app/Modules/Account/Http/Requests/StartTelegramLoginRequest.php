<?php

declare(strict_types=1);

namespace App\Modules\Account\Http\Requests;

use App\Modules\Language\Enums\InterfaceLanguage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StartTelegramLoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Shown in the bot's confirmation, so the person can tell it is their own phone asking.
            'device' => ['required', 'string', 'max:120'],
            // The app's language: the bot speaks it, and a new profile starts in it.
            'ui_language' => ['sometimes', Rule::enum(InterfaceLanguage::class)],
        ];
    }
}
