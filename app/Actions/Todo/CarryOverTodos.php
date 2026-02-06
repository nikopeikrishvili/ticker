<?php

namespace App\Actions\Todo;

use App\Repositories\TodoRepository;
use Illuminate\Database\Eloquent\Collection;

class CarryOverTodos
{
    public function __construct(
        protected TodoRepository $todoRepository
    ) {}

    public function __invoke(array $taskIds, string $targetDate): array
    {
        $movedCount = $this->todoRepository->carryOver($taskIds, $targetDate);
        $movedTasks = $this->todoRepository->getByIds($taskIds);

        return [
            'moved_count' => $movedCount,
            'tasks' => $movedTasks,
        ];
    }
}
