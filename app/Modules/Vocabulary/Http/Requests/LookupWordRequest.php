<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Http\Requests;

use App\Modules\Language\Contracts\LanguageCatalog;
use App\Modules\Language\DTO\TutorLanguage;
use App\Modules\Language\Enums\InterfaceLanguage;
use App\Modules\Language\Rules\TaughtLanguage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class LookupWordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'word' => ['required', 'string', 'max:64'],
            'sentence' => ['required', 'string', 'max:500'],
            // The language the meaning is explained in.
            'language' => ['required', Rule::enum(InterfaceLanguage::class)],
            // The language the word belongs to.
            'target_language' => ['nullable', 'string', TaughtLanguage::make(includeSwitchedOff: true)],
        ];
    }

    public function interfaceLanguage(): InterfaceLanguage
    {
        return InterfaceLanguage::from($this->validated('language'));
    }

    public function targetLanguage(LanguageCatalog $languages): TutorLanguage
    {
        $code = $this->validated('target_language');

        return $code === null ? $languages->default() : $languages->get($code);
    }
}
