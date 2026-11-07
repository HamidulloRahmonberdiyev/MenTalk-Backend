<?php

declare(strict_types=1);

namespace App\Modules\Translator\Http\Requests;

use App\Modules\Language\Enums\InterfaceLanguage;
use App\Modules\Translator\DTO\TranslationQuery;
use App\Modules\Translator\Support\TranslatorLanguages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TranslationRequest extends FormRequest
{
    public function rules(): array
    {
        $codes = TranslatorLanguages::codes();

        return [
            'text' => ['required', 'string', 'max:500'],
            'source' => ['required', 'string', Rule::in([TranslatorLanguages::AUTO, ...$codes])],
            'target' => ['required', 'string', Rule::in($codes)],
            'ui_language' => ['required', Rule::enum(InterfaceLanguage::class)],
            'uz_script' => ['sometimes', Rule::in(['latin', 'cyrillic'])],
        ];
    }

    public function translationQuery(): TranslationQuery
    {
        return new TranslationQuery(
            text: $this->validated('text'),
            source: $this->validated('source'),
            target: $this->validated('target'),
            uiLanguage: InterfaceLanguage::from($this->validated('ui_language')),
            uzScript: $this->validated('uz_script', 'latin'),
        );
    }
}
