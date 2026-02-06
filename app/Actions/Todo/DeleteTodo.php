<?php

namespace App\Actions\Todo;

use App\Models\Todo;
use App\Repositories\TodoRepository;

class DeleteTodo
{
    public function __construct(
        protected TodoRepository $todoRepository
    ) {}

    public function __invoke(Todo $todo): bool
    {
        // Stop any active time tracking first
        $todo->stopWorking();

        return $this->todoRepository->delete($todo);
    }
}
