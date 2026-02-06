<?php

namespace App\Repositories;

use App\Models\RecurringTask;
use Illuminate\Database\Eloquent\Collection;

class RecurringTaskRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new RecurringTask());
    }

    /**
     * Get all recurring tasks ordered by creation date.
     */
    public function getAllOrdered(): Collection
    {
        return $this->query()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get active recurring tasks.
     */
    public function getActive(): Collection
    {
        return $this->query()
            ->active()
            ->get();
    }

    /**
     * Toggle the active status of a recurring task.
     */
    public function toggle(RecurringTask $task): RecurringTask
    {
        $task->update([
            'is_active' => !$task->is_active,
        ]);

        return $task;
    }
}
