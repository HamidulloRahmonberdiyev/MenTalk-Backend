<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Http\Requests;

use App\Modules\Language\Contracts\LanguageCatalog;
use App\Modules\Language\Rules\TaughtLanguage;
use App\Modules\Vocabulary\DTO\ReviewAnswer;
use App\Modules\Vocabulary\Enums\ReviewRating;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

final class RecordReviewsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1', 'max:30'],
            'answers.*.id' => ['nullable', 'ulid'],
            'answers.*.word' => ['required', 'string', 'max:160'],
            'answers.*.language' => ['nullable', 'string', TaughtLanguage::make(includeSwitchedOff: true)],
            'answers.*.rating' => ['required_without:answers.*.correct', Rule::enum(ReviewRating::class)],
            'answers.*.correct' => ['boolean'],
            'answers.*.answered_at' => ['required', 'date'],
            'answers.*.duration_ms' => ['nullable', 'integer', 'min:0', 'max:3600000'],
        ];
    }

    /** @return Collection<int, ReviewAnswer> */
    public function answers(LanguageCatalog $languages): Collection
    {
        $default = $languages->default()->code;

        return collect($this->validated('answers'))->map(fn (array $answer) => ReviewAnswer::fromArray($answer, $default));
    }
}
