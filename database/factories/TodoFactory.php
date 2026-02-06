<?php

namespace Database\Factories;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Todo>
 */
class TodoFactory extends Factory
{
    protected $model = Todo::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'content' => fake()->sentence(),
            'todo_date' => fake()->date(),
            'status' => fake()->randomElement([Todo::STATUS_BACKLOG, Todo::STATUS_TODO, Todo::STATUS_IN_PROGRESS, Todo::STATUS_DONE]),
            'priority' => fake()->numberBetween(1, 5),
            'is_completed' => false,
            'order' => fake()->numberBetween(1, 100),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Todo::STATUS_DONE,
            'is_completed' => true,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Todo::STATUS_IN_PROGRESS,
            'is_completed' => false,
        ]);
    }

    public function todo(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Todo::STATUS_TODO,
            'is_completed' => false,
        ]);
    }

    public function backlog(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Todo::STATUS_BACKLOG,
            'is_completed' => false,
            'todo_date' => null,
        ]);
    }

    public function forToday(): static
    {
        return $this->state(fn (array $attributes) => [
            'todo_date' => now()->toDateString(),
        ]);
    }

    public function forDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'todo_date' => $date,
        ]);
    }
}
