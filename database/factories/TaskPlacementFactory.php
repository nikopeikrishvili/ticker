<?php

namespace Database\Factories;

use App\Models\TaskPlacement;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaskPlacement>
 */
class TaskPlacementFactory extends Factory
{
    protected $model = TaskPlacement::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'todo_id' => Todo::factory(),
            'week_key' => now()->format('Y-\\WW'),
            'day_of_week' => fake()->numberBetween(1, 5),
            'is_current' => true,
            'moved_to_id' => null,
            'order' => fake()->numberBetween(1, 100),
        ];
    }

    public function ghost(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_current' => false,
        ]);
    }
}
