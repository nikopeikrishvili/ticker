<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $key
 * @property string|null $value
 * @property string|null $category
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User|null $user
 */
class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'key',
        'value',
        'category',
    ];

    /**
     * Default settings with their categories.
     */
    public const DEFAULTS = [
        // General
        'general.timezone' => [
            'value' => 'Asia/Tbilisi',
            'category' => 'general',
        ],
        // Appearance
        'appearance.dot_opacity' => [
            'value' => '0.3',
            'category' => 'appearance',
        ],
        'appearance.primary_color' => [
            'value' => '#346ec9',
            'category' => 'appearance',
        ],
        'appearance.background_color' => [
            'value' => '#e5e7eb',
            'category' => 'appearance',
        ],
        'appearance.text_color' => [
            'value' => '#1f2937',
            'category' => 'appearance',
        ],
        'appearance.secondary_text_color' => [
            'value' => '#9ca3af',
            'category' => 'appearance',
        ],
        'appearance.border_color' => [
            'value' => '#e5e7eb',
            'category' => 'appearance',
        ],
        // Integrations - Jira
        'integrations.jira.enabled' => [
            'value' => 'false',
            'category' => 'integrations',
        ],
        'integrations.jira.url' => [
            'value' => '',
            'category' => 'integrations',
        ],
        'integrations.jira.email' => [
            'value' => '',
            'category' => 'integrations',
        ],
        'integrations.jira.api_token' => [
            'value' => '',
            'category' => 'integrations',
        ],
        'integrations.jira.project_key' => [
            'value' => '',
            'category' => 'integrations',
        ],
        'integrations.jira.last_sync_at' => [
            'value' => '',
            'category' => 'integrations',
        ],
    ];

    /**
     * Get the user that owns this setting.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get a setting value by key for a specific user.
     */
    public static function getValue(string $key, mixed $default = null, ?int $userId = null): mixed
    {
        $query = static::where('key', $key);

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $setting = $query->first();

        if ($setting) {
            return $setting->value;
        }

        // Return default from DEFAULTS constant
        if (isset(static::DEFAULTS[$key])) {
            return static::DEFAULTS[$key]['value'];
        }

        return $default;
    }

    /**
     * Set a setting value for a specific user.
     */
    public static function setValue(string $key, mixed $value, ?int $userId = null): static
    {
        $category = static::DEFAULTS[$key]['category'] ?? 'general';

        $conditions = ['key' => $key];
        if ($userId !== null) {
            $conditions['user_id'] = $userId;
        }

        return static::updateOrCreate(
            $conditions,
            ['value' => $value, 'category' => $category, 'user_id' => $userId]
        );
    }

    /**
     * Get all settings as key-value pairs for a specific user.
     */
    public static function getAllSettings(?int $userId = null): array
    {
        $settings = [];

        // Start with defaults
        foreach (static::DEFAULTS as $key => $config) {
            $settings[$key] = $config['value'];
        }

        // Override with database values
        $query = static::query();
        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $query->get()->each(function ($setting) use (&$settings) {
            $settings[$setting->key] = $setting->value;
        });

        return $settings;
    }

    /**
     * Get settings by category for a specific user.
     */
    public static function getByCategory(string $category, ?int $userId = null): array
    {
        $settings = [];

        // Get defaults for this category
        foreach (static::DEFAULTS as $key => $config) {
            if ($config['category'] === $category) {
                $settings[$key] = $config['value'];
            }
        }

        // Override with database values
        $query = static::where('category', $category);
        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $query->get()->each(function ($setting) use (&$settings) {
            $settings[$setting->key] = $setting->value;
        });

        return $settings;
    }
}
