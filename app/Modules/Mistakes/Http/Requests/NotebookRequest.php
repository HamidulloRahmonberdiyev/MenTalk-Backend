<?php

declare(strict_types=1);

namespace App\Modules\Mistakes\Http\Requests;

use App\Modules\Conversation\Enums\MistakeCategory;
use App\Modules\Language\Rules\TaughtLanguage;
use App\Modules\Vocabulary\Enums\WordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Which notebook (one per studied language) and, for the list, which part of it. */
final class NotebookRequest extends FormRequest
{
    public const MAX_PRACTICE = 20;

    public function rules(): array
    {
        return [
            // Switched-off languages included: a notebook outlives the course it was written in.
            'language' => ['required', 'string', TaughtLanguage::make(includeSwitchedOff: true)],
            'category' => ['sometimes', Rule::enum(MistakeCategory::class)],
            'status' => ['sometimes', Rule::enum(WordStatus::class)],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:'.self::MAX_PRACTICE],
        ];
    }

    public function language(): string
    {
        return $this->validated('language');
    }

    public function category(): ?MistakeCategory
    {
        return MistakeCategory::tryFrom((string) $this->validated('category'));
    }

    public function status(): ?WordStatus
    {
        return WordStatus::tryFrom((string) $this->validated('status'));
    }

    public function limit(): int
    {
        return (int) $this->validated('limit', 10);
    }
}
