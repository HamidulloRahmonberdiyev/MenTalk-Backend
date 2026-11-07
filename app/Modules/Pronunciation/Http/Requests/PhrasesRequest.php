<?php

declare(strict_types=1);

namespace App\Modules\Pronunciation\Http\Requests;

use App\Modules\Language\Contracts\LanguageCatalog;
use App\Modules\Language\DTO\TutorLanguage;
use App\Modules\Language\Rules\TaughtLanguage;
use App\Modules\Pronunciation\Enums\PhraseLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PhrasesRequest extends FormRequest
{
    public const MAX_LIMIT = 10;

    public function rules(): array
    {
        return [
            'language' => ['required', 'string', TaughtLanguage::make(includeSwitchedOff: true)],
            'level' => ['required', Rule::enum(PhraseLevel::class)],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:'.self::MAX_LIMIT],
        ];
    }

    public function language(LanguageCatalog $languages): TutorLanguage
    {
        return $languages->get($this->validated('language'));
    }

    public function level(): PhraseLevel
    {
        return PhraseLevel::from($this->validated('level'));
    }

    public function limit(): int
    {
        return (int) $this->validated('limit', 5);
    }
}
