<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class CategoryRepository extends BaseRepository
{
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct()
    {
        parent::__construct(new Category());
    }

    private function getCacheKey(): string
    {
        return 'categories:user:' . $this->getUserId();
    }

    public function clearCache(): void
    {
        Cache::forget($this->getCacheKey());
    }

    /**
     * Get all categories ordered.
     */
    public function getAllOrdered(): Collection
    {
        return Cache::remember($this->getCacheKey(), self::CACHE_TTL, function () {
            return $this->query()
                ->ordered()
                ->get();
        });
    }

    /**
     * Get categories with keywords for auto-detection.
     */
    public function getWithKeywords(): Collection
    {
        return $this->getAllOrdered()->filter(function ($category) {
            return !empty($category->keywords);
        })->values();
    }

    /**
     * Get the next order value.
     */
    public function getNextOrder(): int
    {
        return ($this->query()->max('order') ?? 0) + 1;
    }

    /**
     * Detect category from text based on keywords.
     */
    public function detectFromText(string $text): ?Category
    {
        $textLower = mb_strtolower($text);
        $categories = $this->getWithKeywords();

        foreach ($categories as $category) {
            $keywords = array_map('trim', explode(',', mb_strtolower($category->keywords)));
            foreach ($keywords as $keyword) {
                if (!empty($keyword) && mb_strpos($textLower, $keyword) !== false) {
                    return $category;
                }
            }
        }

        return null;
    }
}
