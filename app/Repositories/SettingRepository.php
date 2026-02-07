<?php

namespace App\Repositories;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingRepository extends BaseRepository
{
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct()
    {
        parent::__construct(new Setting());
    }

    /**
     * Get the cache key for the current user's settings.
     */
    private function getCacheKey(): string
    {
        return 'settings:user:' . $this->getUserId();
    }

    /**
     * Clear the settings cache for the current user.
     */
    public function clearCache(): void
    {
        Cache::forget($this->getCacheKey());
    }

    /**
     * Get a setting value by key.
     */
    public function getValue(string $key, mixed $default = null): mixed
    {
        $allSettings = $this->getAllSettings();

        return $allSettings[$key] ?? $default;
    }

    /**
     * Set a setting value.
     */
    public function setValue(string $key, mixed $value): Setting
    {
        $category = Setting::DEFAULTS[$key]['category'] ?? 'general';

        $setting = Setting::updateOrCreate(
            ['key' => $key, 'user_id' => $this->getUserId()],
            ['value' => $value, 'category' => $category]
        );

        $this->clearCache();

        return $setting;
    }

    /**
     * Get all settings as key-value pairs (cached).
     */
    public function getAllSettings(): array
    {
        return Cache::remember($this->getCacheKey(), self::CACHE_TTL, function () {
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
        });
    }

    /**
     * Get settings by category.
     */
    public function getByCategory(string $category): array
    {
        $allSettings = $this->getAllSettings();
        $categorySettings = [];

        foreach (Setting::DEFAULTS as $key => $config) {
            if ($config['category'] === $category && isset($allSettings[$key])) {
                $categorySettings[$key] = $allSettings[$key];
            }
        }

        return $categorySettings;
    }

    /**
     * Delete a setting by key.
     */
    public function deleteByKey(string $key): int
    {
        $result = $this->query()
            ->where('key', $key)
            ->delete();

        $this->clearCache();

        return $result;
    }

    /**
     * Delete all settings for current user.
     */
    public function deleteAll(): int
    {
        $result = $this->query()->delete();

        $this->clearCache();

        return $result;
    }
}
