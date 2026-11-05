<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Http\Requests;

use App\Modules\Language\Rules\TaughtLanguage;
use Illuminate\Foundation\Http\FormRequest;

final class ShowPathRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Progress made in a language that was switched off since still counts.
            'language' => ['nullable', 'string', TaughtLanguage::make(includeSwitchedOff: true)],
        ];
    }

    public function language(): ?string
    {
        return $this->validated('language');
    }
}
