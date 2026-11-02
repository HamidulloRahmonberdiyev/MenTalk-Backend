<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Contracts\LanguageCatalog;
use App\DTO\ReviewAnswer;
use App\Enums\ReviewRating;
use App\Rules\TaughtLanguage;
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
