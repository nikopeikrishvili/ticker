<?php

namespace App\DTOs;

use Illuminate\Http\Request;

readonly class CreateRecurringTaskData
{
    public function __construct(
        public string $content,
        public string $frequencyType,
        public ?array $weekdays = null,
        public bool $isActive = true,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $validated = $request->validated();
        $weekdays = $validated['weekdays'] ?? null;

        if ($weekdays !== null) {
            sort($weekdays);
        }

        return new self(
            content: $validated['content'],
            frequencyType: $validated['frequency_type'],
            weekdays: $weekdays,
            isActive: $validated['is_active'] ?? true,
        );
    }

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'frequency_type' => $this->frequencyType,
            'weekdays' => $this->weekdays,
            'is_active' => $this->isActive,
        ];
    }
}
