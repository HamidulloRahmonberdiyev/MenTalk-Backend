<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Scenario;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
final class ConversationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'scenario_id' => Scenario::factory(),
            'state' => 'active',
            'started_at' => now(),
        ];
    }
}
