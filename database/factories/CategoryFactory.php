<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->word(),
            'icon' => fake()->randomElement(['briefcase', 'code', 'book', 'heart', 'star']),
            'color' => fake()->hexColor(),
            'keywords' => fake()->words(3, true),
            'order' => fake()->numberBetween(1, 100),
        ];
    }
}
