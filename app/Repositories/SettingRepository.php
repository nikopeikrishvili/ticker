<?php

namespace App\Repositories;

use App\Models\Setting;

class SettingRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Setting());
    }

    /**
     * Get a setting value by key.
     */
    public function getValue(string $key, mixed $default = null): mixed
    {
        $setting = $this->query()
            ->where('key', $key)
            ->first();

        if ($setting) {
            return $setting->value;
        }

        // Return default from DEFAULTS constant
        if (isset(Setting::DEFAULTS[$key])) {
            return Setting::DEFAULTS[$key]['value'];
        }

        return $default;
    }

    /**
     * Set a setting value.
     */
    public function setValue(string $key, mixed $value): Setting
    {
        $category = Setting::DEFAULTS[$key]['category'] ?? 'general';

        return Setting::updateOrCreate(
            ['key' => $key, 'user_id' => $this->getUserId()],
            ['value' => $value, 'category' => $category]
        );
    }

    /**
     * Get all settings as key-value pairs.
     */
    public function getAllSettings(): array
    {
        $settings = [];

        // Start with defaults
        foreach (Setting::DEFAULTS as $key => $config) {
            $settings[$key] = $config['value'];
        }

        // Override with database values
        $this->query()->get()->each(function ($setting) use (&$settings) {
            $settings[$setting->key] = $setting->value;
        });

        return $settings;
    }

    /**
     * Get settings by category.
     */
    public function getByCategory(string $category): array
    {
        $settings = [];

        // Get defaults for this category
        foreach (Setting::DEFAULTS as $key => $config) {
            if ($config['category'] === $category) {
                $settings[$key] = $config['value'];
            }
        }

        // Override with database values
        $this->query()
            ->where('category', $category)
            ->get()
            ->each(function ($setting) use (&$settings) {
                $settings[$setting->key] = $setting->value;
            });

        return $settings;
    }

    /**
     * Delete a setting by key.
     */
    public function deleteByKey(string $key): int
    {
        return $this->query()
            ->where('key', $key)
            ->delete();
    }

    /**
     * Delete all settings for current user.
     */
    public function deleteAll(): int
    {
        return $this->query()->delete();
    }
}
