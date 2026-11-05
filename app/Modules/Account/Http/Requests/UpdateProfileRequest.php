<?php

declare(strict_types=1);

namespace App\Modules\Account\Http\Requests;

use App\Modules\Account\Enums\Experience;
use App\Modules\Account\Enums\Focus;
use App\Modules\Account\Enums\Gender;
use App\Modules\Account\Enums\Level;
use App\Modules\Account\Enums\LevelSource;
use App\Modules\Account\Enums\ReferralSource;
use App\Modules\Language\Enums\InterfaceLanguage;
use App\Modules\Language\Rules\TaughtLanguage;
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
            'level_source' => ['sometimes', 'nullable', Rule::enum(LevelSource::class)],
            // A test result only makes sense together with the level it produced.
            'placement_score' => [
                Rule::requiredIf(fn () => $this->input('level_source') === LevelSource::Placement->value),
                'prohibited_unless:level_source,placement',
                'nullable', 'integer', 'between:0,100',
            ],
            'experience' => ['sometimes', 'nullable', Rule::enum(Experience::class)],
            'referral_source' => ['sometimes', 'nullable', Rule::enum(ReferralSource::class)],
            'focus' => ['sometimes', 'array', 'max:6'],
            'focus.*' => ['distinct', Rule::enum(Focus::class)],
            'goals' => ['sometimes', 'array', 'max:6'],
            'goals.*' => ['string', 'max:24'],
            'ui_language' => ['sometimes', Rule::enum(InterfaceLanguage::class)],
            'target_language' => ['sometimes', 'string', TaughtLanguage::make()],
            'timezone' => ['sometimes', 'timezone:all'],
            'daily_goal_minutes' => ['sometimes', 'integer', 'min:1', 'max:240'],
        ];
    }
}
