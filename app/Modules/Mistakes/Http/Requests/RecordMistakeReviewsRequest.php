<?php

declare(strict_types=1);

namespace App\Modules\Mistakes\Http\Requests;

use App\Modules\Mistakes\Actions\RecordMistakeReviews;
use App\Modules\Mistakes\DTO\MistakeAnswer;
use App\Modules\Vocabulary\Enums\ReviewRating;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

final class RecordMistakeReviewsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1', 'max:'.RecordMistakeReviews::MAX_ANSWERS],
            'answers.*.id' => ['required', 'ulid'],
            'answers.*.rating' => ['required_without:answers.*.correct', Rule::enum(ReviewRating::class)],
            'answers.*.correct' => ['boolean'],
            'answers.*.exercise' => ['nullable', 'string', Rule::in(['choice', 'spot', 'build'])],
            // Not in the future: an answer stamped ahead would push its schedule past the real one.
            'answers.*.answered_at' => ['required', 'date', 'before_or_equal:'.now()->addMinutes(5)->toIso8601String()],
            'answers.*.duration_ms' => ['nullable', 'integer', 'min:0', 'max:3600000'],
        ];
    }

    /** @return Collection<int, MistakeAnswer> */
    public function answers(): Collection
    {
        return collect($this->validated('answers'))->map(fn (array $answer) => MistakeAnswer::fromArray($answer));
    }
}
