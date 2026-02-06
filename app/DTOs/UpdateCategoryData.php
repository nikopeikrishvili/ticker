<?php

namespace App\DTOs;

use Illuminate\Http\Request;

readonly class UpdateCategoryData
{
    public function __construct(
        public ?string $name = null,
        public ?string $icon = null,
        public ?string $color = null,
        public ?string $keywords = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $validated = $request->validated();

        return new self(
            name: $validated['name'] ?? null,
            icon: $validated['icon'] ?? null,
            color: $validated['color'] ?? null,
            keywords: $validated['keywords'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'icon' => $this->icon,
            'color' => $this->color,
            'keywords' => $this->keywords,
        ], fn ($value) => $value !== null);
    }
}
