<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Http\Requests;

use App\Modules\Language\Contracts\LanguageCatalog;
use App\Modules\Language\Rules\TaughtLanguage;
use Illuminate\Foundation\Http\FormRequest;

/** Tutor voices are shown for one language: the one asked for, or else the one the learner studies. */
final class TutorVoiceLanguageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'language' => ['sometimes', 'nullable', 'string', TaughtLanguage::make()],
        ];
    }

    public function language(): string
    {
        $languages = app(LanguageCatalog::class);
        $studied = $this->user()->profile()->value('target_language');

        return $this->validated('language')
            ?? ($studied !== null && $languages->isAvailable($studied) ? $studied : $languages->default()->code);
    }
}
