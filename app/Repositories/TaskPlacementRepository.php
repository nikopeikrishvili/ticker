<?php

namespace App\Repositories;

use App\Models\TaskPlacement;
use Illuminate\Database\Eloquent\Collection;

class TaskPlacementRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new TaskPlacement());
    }

    /**
     * Get placements for a specific week.
     */
    public function getForWeek(string $weekKey): Collection
    {
        return $this->query()
            ->forWeek($weekKey)
            ->with('todo')
            ->ordered()
            ->get();
    }

    /**
     * Get placements for a specific week and day.
     */
    public function getForWeekDay(string $weekKey, int $dayOfWeek): Collection
    {
        return $this->query()
            ->forWeekDay($weekKey, $dayOfWeek)
            ->with('todo')
            ->ordered()
            ->get();
    }

    /**
     * Get current (non-ghost) placements.
     */
    public function getCurrent(): Collection
    {
        return $this->query()
            ->current()
            ->get();
    }

    /**
     * Update order for a placement.
     */
    public function updateOrder(int $id, int $order): int
    {
        return $this->query()
            ->where('id', $id)
            ->update(['order' => $order]);
    }
}
