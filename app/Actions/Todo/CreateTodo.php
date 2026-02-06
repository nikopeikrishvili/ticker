<?php

namespace App\Actions\Todo;

use App\DTOs\CreateTodoData;
use App\Models\Todo;
use App\Repositories\TodoRepository;

class CreateTodo
{
    public function __construct(
        protected TodoRepository $todoRepository
    ) {}

    public function __invoke(CreateTodoData $data): Todo
    {
        $attributes = $data->toArray();
        $attributes['order'] = $this->todoRepository->getNextOrder();

        return $this->todoRepository->create($attributes);
    }
}
