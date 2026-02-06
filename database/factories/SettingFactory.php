<?php

namespace Database\Factories;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Setting>
 */
class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'key' => 'general.timezone',
            'value' => 'Asia/Tbilisi',
            'category' => 'general',
        ];
    }

    public function appearance(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'appearance.primary_color',
            'value' => '#346ec9',
            'category' => 'appearance',
        ]);
    }
}
