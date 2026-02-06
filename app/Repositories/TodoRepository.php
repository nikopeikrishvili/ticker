<?php

namespace App\Repositories;

use App\Models\Todo;
use Illuminate\Database\Eloquent\Collection;

class TodoRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Todo());
    }

    /**
     * Get todos for a specific date.
     */
    public function getForDate(string $date): Collection
    {
        return $this->query()
            ->forDate($date)
            ->ordered()
            ->get();
    }

    /**
     * Get incomplete todos from previous dates.
     */
    public function getPendingFromPreviousDates(string $date): Collection
    {
        return $this->query()
            ->whereNotNull('todo_date')
            ->where('todo_date', '<', $date)
            ->where('is_completed', false)
            ->where('status', '!=', Todo::STATUS_DONE)
            ->ordered()
            ->get();
    }

    /**
     * Get backlog todos (no date assigned, incomplete).
     */
    public function getBacklog(): Collection
    {
        return $this->query()
            ->whereNull('todo_date')
            ->incomplete()
            ->ordered()
            ->get();
    }

    /**
     * Get todos for a specific date range.
     */
    public function getForDateRange(string $startDate, string $endDate): Collection
    {
        return $this->query()
            ->whereBetween('todo_date', [$startDate, $endDate])
            ->ordered()
            ->get();
    }

    /**
     * Get incomplete todos for a date range.
     */
    public function getIncompleteForDateRange(string $startDate, string $endDate): Collection
    {
        return $this->query()
            ->whereBetween('todo_date', [$startDate, $endDate])
            ->incomplete()
            ->get();
    }

    /**
     * Get the next order value.
     */
    public function getNextOrder(): int
    {
        return ($this->query()->max('order') ?? 0) + 1;
    }

    /**
     * Carry over tasks to a new date.
     */
    public function carryOver(array $taskIds, string $targetDate): int
    {
        return $this->query()
            ->whereIn('id', $taskIds)
            ->update(['todo_date' => $targetDate]);
    }

    /**
     * Get todos by IDs.
     */
    public function getByIds(array $ids): Collection
    {
        return $this->query()
            ->whereIn('id', $ids)
            ->get();
    }

    /**
     * Find todo by Jira key.
     */
    public function findByJiraKey(string $jiraKey): ?Todo
    {
        return $this->query()
            ->where('jira_key', $jiraKey)
            ->first();
    }
}
