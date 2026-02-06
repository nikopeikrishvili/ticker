<?php

namespace App\DTOs;

use Illuminate\Http\Request;

readonly class UpdateTodoData
{
    public function __construct(
        public ?string $content = null,
        public ?bool $isCompleted = null,
        public ?string $status = null,
        public ?int $priority = null,
        public ?string $todoDate = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $validated = $request->validated();

        return new self(
            content: $validated['content'] ?? null,
            isCompleted: $validated['is_completed'] ?? null,
            status: $validated['status'] ?? null,
            priority: $validated['priority'] ?? null,
            todoDate: $validated['todo_date'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'content' => $this->content,
            'is_completed' => $this->isCompleted,
            'status' => $this->status,
            'priority' => $this->priority,
            'todo_date' => $this->todoDate,
        ], fn ($value) => $value !== null);
    }

    public function hasStatus(): bool
    {
        return $this->status !== null;
    }
}
