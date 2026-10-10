<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LearnerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearnerProfile>
 */
final class LearnerProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'level' => 'beginner',
            'goals' => ['travel'],
            'ui_language' => 'uz',
            'timezone' => 'Asia/Tashkent',
            'daily_goal_minutes' => 10,
            'xp' => 0,
            'streak' => 0,
        ];
    }
}
