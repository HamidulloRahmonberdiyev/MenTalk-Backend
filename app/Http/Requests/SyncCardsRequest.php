<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Contracts\LanguageCatalog;
use App\DTO\CardChange;
use App\Rules\TaughtLanguage;
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
            // Cards of a language that was later switched off still sync.
            'changes.*.language' => ['nullable', 'string', TaughtLanguage::make(includeSwitchedOff: true)],
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
    public function changes(LanguageCatalog $languages): Collection
    {
        $default = $languages->default()->code;

        return collect($this->validated('changes'))
            ->map(fn (array $change) => CardChange::fromArray($change, $default))
            ->unique(fn (CardChange $change) => "{$change->language}|{$change->word}")
            ->values();
    }
}
