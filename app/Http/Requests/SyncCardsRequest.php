<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\CardChange;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

final class SyncCardsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'cursor' => ['nullable', 'date'],
            'changes' => ['present', 'array', 'max:500'],
            'changes.*.word' => ['required', 'string', 'max:64'],
            'changes.*.translation' => ['required', 'string', 'max:160'],
            'changes.*.example' => ['nullable', 'string', 'max:1000'],
            'changes.*.form' => ['nullable', 'string', 'max:64'],
            'changes.*.stage' => ['required', 'integer', 'min:0', 'max:6'],
            'changes.*.streak' => ['integer', 'min:0', 'max:65535'],
            'changes.*.lapses' => ['integer', 'min:0', 'max:65535'],
            'changes.*.due_at' => ['required', 'date'],
            'changes.*.updated_at' => ['required', 'date'],
            'changes.*.deleted' => ['boolean'],
        ];
    }

    public function cursor(): ?CarbonImmutable
    {
        return $this->filled('cursor') ? CarbonImmutable::parse($this->string('cursor')->toString()) : null;
    }

    /** @return Collection<int, CardChange> */
    public function changes(): Collection
    {
        return collect($this->validated('changes'))
            ->unique('word')
            ->map(fn (array $change) => CardChange::fromArray($change))
            ->values();
    }
}
