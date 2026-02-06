<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int|null $user_id
 * @property Carbon|null $todo_date
 * @property string $content
 * @property string $status
 * @property int $priority
 * @property bool $is_completed
 * @property int $order
 * @property string|null $current_week_key
 * @property int|null $current_day
 * @property string|null $task_id
 * @property string|null $jira_key
 * @property string|null $jira_url
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read string $display_id
 * @property-read string $status_label
 * @property-read bool $is_working
 * @property-read string|null $working_started_at
 * @property-read int $total_time_spent
 * @property-read User|null $user
 * @property-read Collection<int, TimeLog> $timeLogs
 * @property-read TimeLog|null $activeTimeLog
 * @property-read Collection<int, TaskPlacement> $placements
 * @property-read TaskPlacement|null $currentPlacement
 */
class Todo extends Model
{
    use HasFactory;

    // Status constants
    const STATUS_BACKLOG = 'backlog';
    const STATUS_TODO = 'todo';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_DONE = 'done';

    public static array $statuses = [
        self::STATUS_BACKLOG => 'ბექლოგი',
        self::STATUS_TODO => 'გასაკეთებელი',
        self::STATUS_IN_PROGRESS => 'მიმდინარე',
        self::STATUS_DONE => 'დასრულებული',
    ];

    // Priority constants
    const PRIORITY_LOWEST = 1;
    const PRIORITY_LOW = 2;
    const PRIORITY_MEDIUM = 3;
    const PRIORITY_HIGH = 4;
    const PRIORITY_HIGHEST = 5;

    protected $fillable = [
        'user_id',
        'todo_date',
        'content',
        'status',
        'priority',
        'is_completed',
        'order',
        'current_week_key',
        'current_day',
        'task_id',
        'jira_key',
        'jira_url',
    ];

    protected $casts = [
        'todo_date' => 'date:Y-m-d',
        'is_completed' => 'boolean',
        'order' => 'integer',
        'priority' => 'integer',
        'current_day' => 'integer',
    ];

    protected $appends = ['display_id', 'is_working', 'working_started_at', 'total_time_spent', 'status_label'];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($todo) {
            if (empty($todo->task_id)) {
                $todo->task_id = 'TASK-' . $todo->id;
                $todo->saveQuietly();
            }
        });

        // Sync is_completed with status
        static::saving(function ($todo) {
            if ($todo->isDirty('status')) {
                $todo->is_completed = $todo->status === self::STATUS_DONE;
            } elseif ($todo->isDirty('is_completed')) {
                if ($todo->is_completed && $todo->status !== self::STATUS_DONE) {
                    $todo->status = self::STATUS_DONE;
                } elseif (!$todo->is_completed && $todo->status === self::STATUS_DONE) {
                    $todo->status = self::STATUS_TODO;
                }
            }
        });
    }

    /**
     * Check if this todo is linked to Jira.
     */
    public function isJiraLinked(): bool
    {
        return !empty($this->jira_key);
    }

    /**
     * Get the display ID (Jira key if linked, otherwise task_id).
     */
    public function getDisplayIdAttribute(): string
    {
        return $this->jira_key ?? $this->task_id ?? 'TASK-' . $this->id;
    }

    /**
     * Get the status label in Georgian.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::$statuses[$this->status] ?? $this->status;
    }

    /**
     * Get the user that owns this todo.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all time logs for this todo.
     */
    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }

    /**
     * Get the currently running time log (no end_time).
     */
    public function activeTimeLog(): HasOne
    {
        return $this->hasOne(TimeLog::class)->whereNull('end_time')->latest();
    }

    /**
     * Check if currently working on this task.
     */
    public function getIsWorkingAttribute(): bool
    {
        return $this->timeLogs()->whereNull('end_time')->exists();
    }

    /**
     * Get the start time of the current work session (ISO 8601 format).
     */
    public function getWorkingStartedAtAttribute(): ?string
    {
        $activeLog = $this->timeLogs()->whereNull('end_time')->first();

        if ($activeLog && $activeLog->start_time) {
            // Combine log_date with start_time to get full datetime
            $date = $activeLog->log_date?->format('Y-m-d') ?? now()->format('Y-m-d');
            $time = $activeLog->start_time instanceof \Carbon\Carbon
                ? $activeLog->start_time->format('H:i:s')
                : $activeLog->start_time;

            return \Carbon\Carbon::parse("{$date} {$time}")->toIso8601String();
        }

        return null;
    }

    /**
     * Get total time spent on this task in minutes.
     */
    public function getTotalTimeSpentAttribute(): int
    {
        $total = 0;
        foreach ($this->timeLogs()->whereNotNull('end_time')->get() as $log) {
            $total += $log->duration_minutes;
        }
        return $total;
    }

    /**
     * Stop all currently running time logs across all tasks for a specific user.
     */
    public static function stopAllWorking(?int $userId = null): array
    {
        $stoppedTodos = [];

        // Find all active time logs (no end_time) for this user
        $query = TimeLog::whereNull('end_time');
        if ($userId !== null) {
            $query->where('user_id', $userId);
        }
        $activeLogs = $query->get();

        foreach ($activeLogs as $log) {
            $log->end_time = now()->format('H:i:s');
            $log->save();

            if ($log->todo_id) {
                $stoppedTodos[] = $log->todo_id;
            }
        }

        return $stoppedTodos;
    }

    /**
     * Start working on this task.
     */
    public function startWorking(): TimeLog
    {
        // Stop ALL currently running time logs (any task) for this user
        self::stopAllWorking($this->user_id);

        // Update status to in_progress if it's todo or backlog
        if (in_array($this->status, [self::STATUS_TODO, self::STATUS_BACKLOG])) {
            $this->status = self::STATUS_IN_PROGRESS;
            $this->save();
        }

        // Create new time log
        return $this->timeLogs()->create([
            'user_id' => $this->user_id,
            'log_date' => now()->toDateString(),
            'start_time' => now()->format('H:i:s'),
            'description' => $this->content,
        ]);
    }

    /**
     * Stop working on this task.
     */
    public function stopWorking(): ?TimeLog
    {
        $activeLog = $this->timeLogs()->whereNull('end_time')->first();

        if ($activeLog) {
            $activeLog->end_time = now()->format('H:i:s');
            $activeLog->save();
            return $activeLog;
        }

        return null;
    }

    /**
     * Scope to filter by date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('todo_date', $date);
    }

    /**
     * Get all placements for this todo.
     */
    public function placements(): HasMany
    {
        return $this->hasMany(TaskPlacement::class);
    }

    /**
     * Get the current (active) placement for this todo.
     */
    public function currentPlacement(): HasOne
    {
        return $this->hasOne(TaskPlacement::class)->where('is_current', true);
    }

    /**
     * Scope to order todos by status (pending first), priority (highest first), then order.
     */
    public function scopeOrdered($query)
    {
        return $query
            ->orderByRaw("CASE WHEN status = 'done' THEN 1 ELSE 0 END")
            ->orderByDesc('priority')
            ->orderBy('order')
            ->orderBy('created_at');
    }

    /**
     * Scope to get only incomplete todos.
     */
    public function scopeIncomplete($query)
    {
        return $query->where('is_completed', false);
    }

    /**
     * Scope to get only completed todos.
     */
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    /**
     * Scope to get todos in backlog (not assigned to any week).
     */
    public function scopeInBacklog($query)
    {
        return $query->whereNull('current_week_key');
    }

    /**
     * Scope to get todos for a specific week.
     */
    public function scopeForWeek($query, string $weekKey)
    {
        return $query->where('current_week_key', $weekKey);
    }

    /**
     * Scope to get todos for a specific week and day.
     */
    public function scopeForDay($query, string $weekKey, int $dayOfWeek)
    {
        return $query->where('current_week_key', $weekKey)
                     ->where('current_day', $dayOfWeek);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
