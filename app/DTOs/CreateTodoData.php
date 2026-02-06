<?php

namespace App\DTOs;

use App\Models\Todo;
use Illuminate\Http\Request;

readonly class CreateTodoData
{
    public function __construct(
        public string $content,
        public ?string $todoDate = null,
        public string $status = Todo::STATUS_TODO,
        public int $priority = Todo::PRIORITY_MEDIUM,
    ) {}

    public static function fromRequest(Request $request, ?string $defaultDate = null): self
    {
        $inputData = $request->all();
        $todoDate = array_key_exists('todo_date', $inputData)
            ? $request->input('todo_date')
            : $defaultDate;

        return new self(
            content: $request->validated('content'),
            todoDate: $todoDate,
            status: $request->validated('status', Todo::STATUS_TODO),
            priority: $request->validated('priority', Todo::PRIORITY_MEDIUM),
        );
    }

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'todo_date' => $this->todoDate,
            'status' => $this->status,
            'priority' => $this->priority,
            'is_completed' => false,
        ];
    }
}
