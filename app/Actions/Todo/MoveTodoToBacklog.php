<?php

namespace App\Actions\Todo;

use App\Models\Todo;
use App\Repositories\TodoRepository;

class MoveTodoToBacklog
{
    public function __construct(
        protected TodoRepository $todoRepository
    ) {}

    public function __invoke(Todo $todo): Todo
    {
        $this->todoRepository->update($todo, [
            'todo_date' => null,
        ]);

        return $todo->fresh();
    }
}
