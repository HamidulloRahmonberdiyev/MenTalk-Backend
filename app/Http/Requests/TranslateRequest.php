<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\Languages\LanguageNames;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

final class TranslateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:500'],
            // Any interface language or taught language.
            'language' => ['required', 'string', function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_string($value) || $this->names()->of($value) === null) {
                    $fail('The :attribute is not a supported language.');
                }
            }],
        ];
    }

    public function languageName(): string
    {
        return (string) $this->names()->of($this->validated('language'));
    }

    private function names(): LanguageNames
    {
        return app(LanguageNames::class);
    }
}
