<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Scenario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Scenario>
 */
final class ScenarioFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => fake()->unique()->slug(1),
            'icon' => 'cafe',
            'tint' => '#F59E0B',
            'gradient' => ['#F59E0B', '#EF4444'],
            'title' => ['uz' => 'Kafe', 'ru' => 'Кафе', 'en' => 'Cafe'],
            'subtitle' => ['uz' => 'Kofe buyurtma qiling', 'ru' => 'Закажите кофе', 'en' => 'Order a coffee'],
            'sort' => 0,
            'active' => true,
        ];
    }

    /** Playable in the given languages. */
    public function playableIn(string ...$languages): self
    {
        return $this->afterCreating(function (Scenario $scenario) use ($languages): void {
            foreach ($languages as $language) {
                $scenario->briefs()->create([
                    'language' => $language,
                    'brief' => 'A small cafe. You are {tutor}, the waiter.',
                    'next_topics' => 'drinks, desserts, paying the bill',
                ]);
            }
        });
    }
}
