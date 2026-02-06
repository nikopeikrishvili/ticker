<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\TimeLog;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimeLog>
 */
class TimeLogFactory extends Factory
{
    protected $model = TimeLog::class;

    public function definition(): array
    {
        $startHour = fake()->numberBetween(8, 16);
        $endHour = $startHour + fake()->numberBetween(1, 3);

        return [
            'user_id' => User::factory(),
            'todo_id' => null,
            'category_id' => null,
            'log_date' => fake()->date(),
            'start_time' => sprintf('%02d:00', $startHour),
            'end_time' => sprintf('%02d:00', $endHour),
            'description' => fake()->sentence(),
        ];
    }

    public function forTodo(Todo $todo): static
    {
        return $this->state(fn (array $attributes) => [
            'todo_id' => $todo->id,
            'user_id' => $todo->user_id,
        ]);
    }

    public function forCategory(Category $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $category->id,
            'user_id' => $category->user_id,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'end_time' => null,
        ]);
    }

    public function forToday(): static
    {
        return $this->state(fn (array $attributes) => [
            'log_date' => now()->toDateString(),
        ]);
    }
}
