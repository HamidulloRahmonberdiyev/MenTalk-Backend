<?php

declare(strict_types=1);

namespace App\Modules\Support\Http\Requests;

use App\Modules\Support\Enums\TicketCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreTicketRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::enum(TicketCategory::class)],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
            'contact' => ['nullable', 'string', 'max:120'],
            'app_version' => ['nullable', 'string', 'max:20'],
            'platform' => ['nullable', 'string', 'in:ios,android,web'],
            'os_version' => ['nullable', 'string', 'max:32'],
            'ui_language' => ['nullable', 'string', 'in:uz,ru,en'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'message' => trim((string) $this->input('message')),
            'contact' => trim((string) $this->input('contact')) ?: null,
        ]);
    }
}
