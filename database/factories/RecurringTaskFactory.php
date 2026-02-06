<?php

namespace Database\Factories;

use App\Models\RecurringTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecurringTask>
 */
class RecurringTaskFactory extends Factory
{
    protected $model = RecurringTask::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'content' => fake()->sentence(),
            'frequency_type' => fake()->randomElement(['daily', 'weekly']),
            'weekdays' => [1, 3, 5], // Mon, Wed, Fri
            'is_active' => true,
            'last_generated_date' => null,
        ];
    }

    public function daily(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency_type' => 'daily',
            'weekdays' => null,
        ]);
    }

    public function weekly(array $weekdays = [1, 2, 3, 4, 5]): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency_type' => 'weekly',
            'weekdays' => $weekdays,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
