<?php

namespace App\DTOs;

use Illuminate\Http\Request;

readonly class UpdateRecurringTaskData
{
    public function __construct(
        public ?string $content = null,
        public ?string $frequencyType = null,
        public ?array $weekdays = null,
        public ?bool $isActive = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $validated = $request->validated();
        $weekdays = $validated['weekdays'] ?? null;

        if ($weekdays !== null) {
            sort($weekdays);
        }

        return new self(
            content: $validated['content'] ?? null,
            frequencyType: $validated['frequency_type'] ?? null,
            weekdays: $weekdays,
            isActive: $validated['is_active'] ?? null,
        );
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->content !== null) {
            $data['content'] = $this->content;
        }
        if ($this->frequencyType !== null) {
            $data['frequency_type'] = $this->frequencyType;
        }
        if ($this->weekdays !== null) {
            $data['weekdays'] = $this->weekdays;
        }
        if ($this->isActive !== null) {
            $data['is_active'] = $this->isActive;
        }

        return $data;
    }
}
