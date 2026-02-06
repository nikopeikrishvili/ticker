<?php

namespace App\DTOs;

use Illuminate\Http\Request;

readonly class CreateCategoryData
{
    public function __construct(
        public string $name,
        public string $icon = 'tag',
        public string $color = '#6b7280',
        public ?string $keywords = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $validated = $request->validated();

        return new self(
            name: $validated['name'],
            icon: $validated['icon'] ?? 'tag',
            color: $validated['color'] ?? '#6b7280',
            keywords: $validated['keywords'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'icon' => $this->icon,
            'color' => $this->color,
            'keywords' => $this->keywords,
        ];
    }
}
