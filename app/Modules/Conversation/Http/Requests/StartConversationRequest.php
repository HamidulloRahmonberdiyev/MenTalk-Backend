<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Http\Requests;

use App\Modules\Language\Rules\TaughtLanguage;
use Illuminate\Foundation\Http\FormRequest;

final class StartConversationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'scenario_id' => ['required', 'string', 'max:24'],
            'target_language' => ['nullable', 'string', TaughtLanguage::make()],
            // Answer at once with the conversation only; the app streams the tutor's first line.
            'defer_opening' => ['sometimes', 'boolean'],
            // A tutor voice id (`GET tutor-voices`). Not checked against the list on purpose: one removed
            // since the app loaded it just means the default voice.
            'voice' => ['sometimes', 'nullable', 'integer'],
        ];
    }

    public function voiceId(): ?int
    {
        $voice = $this->validated('voice');

        return $voice === null ? null : (int) $voice;
    }

    /** Null when the client sent none: the learner's profile decides. */
    public function targetLanguage(): ?string
    {
        return $this->validated('target_language');
    }
}
