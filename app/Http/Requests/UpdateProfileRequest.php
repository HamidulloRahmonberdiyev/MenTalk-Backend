<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Enums\InterfaceLanguage;
use App\Enums\Level;
use App\Rules\TaughtLanguage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'min:2', 'max:120'],
            'gender' => ['sometimes', 'nullable', Rule::enum(Gender::class)],
            'country' => ['sometimes', 'nullable', 'string', 'size:2'],
            'birth_date' => ['sometimes', 'nullable', 'date', 'before:today'],
            'level' => ['sometimes', 'nullable', Rule::enum(Level::class)],
            'goals' => ['sometimes', 'array', 'max:6'],
            'goals.*' => ['string', 'max:24'],
            'ui_language' => ['sometimes', Rule::enum(InterfaceLanguage::class)],
            'target_language' => ['sometimes', 'string', TaughtLanguage::make()],
            'timezone' => ['sometimes', 'timezone:all'],
            'daily_goal_minutes' => ['sometimes', 'integer', 'min:1', 'max:240'],
        ];
    }
}
