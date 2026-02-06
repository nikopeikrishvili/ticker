<?php

namespace App\Actions\Todo;

use App\DTOs\UpdateTodoData;
use App\Models\Todo;
use App\Repositories\TodoRepository;

class UpdateTodo
{
    public function __construct(
        protected TodoRepository $todoRepository
    ) {}

    public function __invoke(Todo $todo, UpdateTodoData $data): Todo
    {
        // Handle status change with automatic timer start/stop
        if ($data->hasStatus()) {
            $newStatus = $data->status;
            $oldStatus = $todo->status;

            if ($newStatus !== $oldStatus) {
                // Start timer when changing to in_progress
                if ($newStatus === Todo::STATUS_IN_PROGRESS && !$todo->is_working) {
                    $this->todoRepository->update($todo, $data->toArray());
                    $todo->startWorking();
                    return $todo->fresh();
                }

                // Stop timer when changing to done
                if ($newStatus === Todo::STATUS_DONE && $todo->is_working) {
                    $todo->stopWorking();
                }
            }
        }

        $this->todoRepository->update($todo, $data->toArray());

        return $todo->fresh();
    }
}
