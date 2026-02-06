<?php

namespace App\DTOs;

use Illuminate\Http\Request;

readonly class CreateTimeLogData
{
    public function __construct(
        public string $startTime,
        public ?string $logDate = null,
        public ?string $endTime = null,
        public ?string $description = null,
        public ?int $categoryId = null,
        public ?int $todoId = null,
    ) {}

    public static function fromRequest(Request $request, ?string $defaultDate = null): self
    {
        $validated = $request->validated();

        return new self(
            startTime: $validated['start_time'],
            logDate: $validated['log_date'] ?? $defaultDate,
            endTime: $validated['end_time'] ?? null,
            description: $validated['description'] ?? null,
            categoryId: $validated['category_id'] ?? null,
            todoId: $validated['todo_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'log_date' => $this->logDate,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'description' => $this->description,
            'category_id' => $this->categoryId,
            'todo_id' => $this->todoId,
        ], fn ($value) => $value !== null);
    }

    public function withCategoryId(int $categoryId): self
    {
        return new self(
            startTime: $this->startTime,
            logDate: $this->logDate,
            endTime: $this->endTime,
            description: $this->description,
            categoryId: $categoryId,
            todoId: $this->todoId,
        );
    }
}
