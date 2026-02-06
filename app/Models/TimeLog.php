<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int|null $todo_id
 * @property int|null $category_id
 * @property Carbon|null $log_date
 * @property Carbon|null $start_time
 * @property Carbon|null $end_time
 * @property string|null $description
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read int $duration_minutes
 * @property-read int $duration
 * @property-read string $formatted_duration
 * @property-read User|null $user
 * @property-read Todo|null $todo
 * @property-read Category|null $category
 */
class TimeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'todo_id',
        'category_id',
        'log_date',
        'start_time',
        'end_time',
        'description',
    ];

    protected $appends = ['duration_minutes', 'formatted_duration'];

    /**
     * Get the user that owns this time log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category that this time log belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the todo that this time log belongs to.
     */
    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }

    protected $casts = [
        'log_date' => 'date:Y-m-d',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
    ];

    /**
     * Scope to filter by date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('log_date', $date);
    }

    /**
     * Scope to order time logs by start time (most recent first).
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('start_time', 'desc');
    }

    /**
     * Get the duration in minutes (calculated in PHP for reliability).
     */
    public function getDurationMinutesAttribute()
    {
        if (!$this->end_time || !$this->start_time) {
            return 0;
        }

        $start = $this->start_time;
        $end = $this->end_time;

        // If they're strings, parse them
        if (is_string($start)) {
            $start = \Carbon\Carbon::parse($start);
        }
        if (is_string($end)) {
            $end = \Carbon\Carbon::parse($end);
        }

        // Use absolute difference in seconds, then convert to minutes
        $seconds = abs($end->diffInSeconds($start));
        return (int) floor($seconds / 60);
    }

    /**
     * Get the duration in minutes (alias).
     */
    public function getDurationAttribute()
    {
        return $this->duration_minutes;
    }

    /**
     * Format duration as HH:MM.
     */
    public function getFormattedDurationAttribute()
    {
        if (!$this->end_time || !$this->start_time) {
            return '--:--';
        }

        $minutes = $this->duration_minutes;
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        return sprintf('%02d:%02d', $hours, $mins);
    }
}
