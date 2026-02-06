<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int $todo_id
 * @property string $week_key
 * @property int $day_of_week
 * @property bool $is_current
 * @property int|null $moved_to_id
 * @property int $order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read string|null $moved_to_label
 * @property-read User|null $user
 * @property-read Todo $todo
 * @property-read TaskPlacement|null $movedTo
 */
class TaskPlacement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'todo_id',
        'week_key',
        'day_of_week',
        'is_current',
        'moved_to_id',
        'order',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_current' => 'boolean',
        'order' => 'integer',
    ];

    protected $appends = ['moved_to_label'];

    /**
     * Georgian day names for display
     */
    public const DAY_NAMES = [
        1 => 'ორშაბათი',
        2 => 'სამშაბათი',
        3 => 'ოთხშაბათი',
        4 => 'ხუთშაბათი',
        5 => 'პარასკევი',
    ];

    /**
     * Get the user that owns this placement.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the todo that owns this placement.
     */
    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }

    /**
     * Get the placement this was moved to.
     */
    public function movedTo(): BelongsTo
    {
        return $this->belongsTo(TaskPlacement::class, 'moved_to_id');
    }

    /**
     * Get the Georgian text label for where this task moved to.
     */
    public function getMovedToLabelAttribute(): ?string
    {
        if (!$this->moved_to_id || $this->is_current) {
            return null;
        }

        $movedTo = $this->movedTo;
        if (!$movedTo) {
            return null;
        }

        $dayName = self::DAY_NAMES[$movedTo->day_of_week] ?? '';
        return "გადავიდა {$dayName}ზე";
    }

    /**
     * Scope to get only current (non-ghost) placements.
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope to get only ghost placements.
     */
    public function scopeGhosts($query)
    {
        return $query->where('is_current', false);
    }

    /**
     * Scope to get placements for a specific week and day.
     */
    public function scopeForWeekDay($query, string $weekKey, int $dayOfWeek)
    {
        return $query->where('week_key', $weekKey)
                     ->where('day_of_week', $dayOfWeek);
    }

    /**
     * Scope to get placements for a specific week.
     */
    public function scopeForWeek($query, string $weekKey)
    {
        return $query->where('week_key', $weekKey);
    }

    /**
     * Scope to order by order field.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
