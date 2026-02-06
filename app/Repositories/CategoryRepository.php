<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Category());
    }

    /**
     * Get all categories ordered.
     */
    public function getAllOrdered(): Collection
    {
        return $this->query()
            ->ordered()
            ->get();
    }

    /**
     * Get categories with keywords for auto-detection.
     */
    public function getWithKeywords(): Collection
    {
        return $this->query()
            ->whereNotNull('keywords')
            ->where('keywords', '!=', '')
            ->get();
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
