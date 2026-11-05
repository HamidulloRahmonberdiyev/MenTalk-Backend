<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Http\Requests;

use App\Modules\Language\Contracts\LanguageCatalog;
use App\Modules\Language\Enums\InterfaceLanguage;
use App\Modules\Language\Rules\TaughtLanguage;
use App\Modules\Vocabulary\DTO\UserWordChange;
use App\Modules\Vocabulary\Enums\WordSource;
use App\Modules\Vocabulary\Enums\WordStatus;
use App\Modules\Vocabulary\Services\WordNormalizer;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

final class SyncUserWordsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'cursor' => ['nullable', 'date'],
            'changes' => ['present', 'array', 'max:500'],
            'changes.*.id' => ['nullable', 'ulid'],
            'changes.*.word' => ['required', 'string', 'max:160'],
            // Words of a language that was later switched off still sync.
            'changes.*.language' => ['nullable', 'string', TaughtLanguage::make(includeSwitchedOff: true)],
            'changes.*.translation' => ['nullable', 'string', 'max:160'],
            // A word explained in its own language (Gloss) is translated into a taught language.
            'changes.*.translation_language' => ['nullable', 'string', Rule::in([
                ...array_column(InterfaceLanguage::cases(), 'value'),
                ...array_map(fn ($language) => $language->code, app(LanguageCatalog::class)->available()),
            ])],
            'changes.*.form' => ['nullable', 'string', 'max:160'],
            'changes.*.context_sentence' => ['nullable', 'string', 'max:1000'],
            'changes.*.source' => ['nullable', Rule::enum(WordSource::class)],
            'changes.*.status' => ['required', Rule::enum(WordStatus::class)],
            'changes.*.ease' => ['required', 'numeric', 'between:1.3,9.99'],
            'changes.*.interval_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'changes.*.repetitions' => ['integer', 'min:0', 'max:65535'],
            'changes.*.lapses' => ['integer', 'min:0', 'max:65535'],
            'changes.*.due_at' => ['required', 'date'],
            'changes.*.last_reviewed_at' => ['nullable', 'date'],
            'changes.*.updated_at' => ['required', 'date'],
            'changes.*.deleted' => ['boolean'],
        ];
    }

    public function cursor(): ?CarbonImmutable
    {
        return $this->filled('cursor') ? CarbonImmutable::parse($this->string('cursor')->toString()) : null;
    }

    /** @return Collection<int, UserWordChange> */
    public function changes(LanguageCatalog $languages, WordNormalizer $normalizer): Collection
    {
        $default = $languages->default()->code;
        $interface = ($this->user()->profile?->interfaceLanguage() ?? InterfaceLanguage::Uzbek)->value;

        return collect($this->validated('changes'))
            ->map(fn (array $change) => UserWordChange::fromArray($change, $default, $interface))
            ->unique(fn (UserWordChange $change) => $change->id ?? $change->language.'|'.$normalizer->normalize($change->word, $change->language))
            ->values();
    }
}
