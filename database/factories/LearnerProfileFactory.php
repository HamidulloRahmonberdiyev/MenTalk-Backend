<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Account\Models\LearnerProfile;
use App\Modules\Account\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearnerProfile>
 */
final class LearnerProfileFactory extends Factory
{
    protected $model = LearnerProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'level' => 'beginner',
            'goals' => ['travel'],
            'ui_language' => 'uz',
            'target_language' => 'ru',
            'timezone' => 'Asia/Tashkent',
            'daily_goal_minutes' => 10,
            'xp' => 0,
            'streak' => 0,
        ];
    }
}
