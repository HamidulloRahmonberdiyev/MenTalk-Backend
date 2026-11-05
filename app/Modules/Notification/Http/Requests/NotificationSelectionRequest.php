<?php

declare(strict_types=1);

namespace App\Modules\Notification\Http\Requests;

use App\Modules\Account\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Which of the learner's notifications an action applies to: the listed `ids`, or `all` of them
 * (optionally only those created up to `before`, so one that arrived after the learner looked
 * at the list is not swept up with the rest).
 */
final class NotificationSelectionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'all' => ['sometimes', 'boolean'],
            'ids' => [Rule::requiredIf(! $this->boolean('all')), 'array', 'max:100'],
            'ids.*' => ['uuid'],
            'before' => ['nullable', 'date'],
        ];
    }

    public function selection(): MorphMany
    {
        /** @var User $user */
        $user = $this->user();

        return $user->notifications()
            ->reorder()
            ->when(! $this->boolean('all'), fn ($query) => $query->whereKey($this->validated('ids')))
            ->when($this->validated('before'), fn ($query, string $before) => $query->where('created_at', '<=', $this->date('before')));
    }
}
