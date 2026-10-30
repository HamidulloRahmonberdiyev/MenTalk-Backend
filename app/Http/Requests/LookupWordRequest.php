<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Contracts\LanguageCatalog;
use App\DTO\TutorLanguage;
use App\Enums\InterfaceLanguage;
use App\Rules\TaughtLanguage;
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
