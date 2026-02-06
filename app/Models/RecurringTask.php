<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $content
 * @property string $frequency_type
 * @property array|null $weekdays
 * @property bool $is_active
 * @property Carbon|null $last_generated_date
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read array $weekday_names
 * @property-read string $schedule_description
 * @property-read User|null $user
 */
class RecurringTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content',
        'frequency_type',
        'weekdays',
        'is_active',
        'last_generated_date',
    ];

    protected $casts = [
        'weekdays' => 'array',
        'is_active' => 'boolean',
        'last_generated_date' => 'date',
    ];

    /**
     * Get the user that owns this recurring task.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get only active recurring tasks.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if this task should run on a given date.
     */
    public function shouldRunOn(\DateTimeInterface $date): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->frequency_type === 'daily') {
            return true;
        }

        if ($this->frequency_type === 'weekly' && $this->weekdays) {
            // PHP: 1=Monday, 7=Sunday (ISO-8601)
            $dayOfWeek = (int) $date->format('N');
            return in_array($dayOfWeek, $this->weekdays);
        }

        return false;
    }

    /**
     * Get weekday names for display.
     */
    public function getWeekdayNamesAttribute(): array
    {
        $names = [
            1 => 'ორშაბათი',
            2 => 'სამშაბათი',
            3 => 'ოთხშაბათი',
            4 => 'ხუთშაბათი',
            5 => 'პარასკევი',
            6 => 'შაბათი',
            7 => 'კვირა',
        ];

        if (!$this->weekdays) {
            return [];
        }

        return array_map(fn($day) => $names[$day] ?? '', $this->weekdays);
    }

    /**
     * Get a human-readable schedule description.
     */
    public function getScheduleDescriptionAttribute(): string
    {
        if ($this->frequency_type === 'daily') {
            return 'ყოველდღე';
        }

        if ($this->frequency_type === 'weekly' && $this->weekdays) {
            $shortNames = [
                1 => 'ორშ',
                2 => 'სამ',
                3 => 'ოთხ',
                4 => 'ხუთ',
                5 => 'პარ',
                6 => 'შაბ',
                7 => 'კვი',
            ];

            $days = array_map(fn($day) => $shortNames[$day] ?? '', $this->weekdays);
            return implode(', ', $days);
        }

        return '';
    }
}
