<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\TaughtLanguage;
use Illuminate\Foundation\Http\FormRequest;

final class StartConversationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'scenario_id' => ['required', 'string', 'max:24'],
            'target_language' => ['nullable', 'string', TaughtLanguage::make()],
        ];
    }

    /** Null when the client sent none: the learner's profile decides. */
    public function targetLanguage(): ?string
    {
        return $this->validated('target_language');
    }
}
