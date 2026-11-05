<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Account\Models\User;
use App\Modules\Conversation\Models\Conversation;
use App\Modules\Conversation\Models\Scenario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
final class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

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
