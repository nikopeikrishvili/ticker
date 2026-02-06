<?php

namespace App\Repositories;

use App\Models\TimeLog;
use Illuminate\Database\Eloquent\Collection;

class TimeLogRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new TimeLog());
    }

    /**
     * Get time logs for a specific date with relationships.
     */
    public function getForDate(string $date): Collection
    {
        return $this->query()
            ->with(['todo', 'category'])
            ->forDate($date)
            ->latest()
            ->get();
    }

    /**
     * Get active (running) time logs.
     */
    public function getActive(): Collection
    {
        return $this->query()
            ->whereNull('end_time')
            ->get();
    }

    /**
     * Stop all active time logs for the current user.
     */
    public function stopAllActive(): array
    {
        $stoppedTodoIds = [];

        $activeLogs = $this->getActive();

        foreach ($activeLogs as $log) {
            $log->end_time = now()->format('H:i:s');
            $log->save();

            if ($log->todo_id) {
                $stoppedTodoIds[] = $log->todo_id;
            }
        }

        return $stoppedTodoIds;
    }
}
