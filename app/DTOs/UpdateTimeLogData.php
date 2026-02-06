<?php

namespace App\DTOs;

use Illuminate\Http\Request;

readonly class UpdateTimeLogData
{
    public function __construct(
        public ?string $startTime = null,
        public ?string $endTime = null,
        public ?string $description = null,
        public ?int $categoryId = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $validated = $request->validated();

        return new self(
            startTime: $validated['start_time'] ?? null,
            endTime: $validated['end_time'] ?? null,
            description: $validated['description'] ?? null,
            categoryId: $validated['category_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'description' => $this->description,
            'category_id' => $this->categoryId,
        ], fn ($value) => $value !== null);
    }
}
