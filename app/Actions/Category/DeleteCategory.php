<?php

namespace App\Actions\Category;

use App\Models\Category;
use App\Repositories\CategoryRepository;

class DeleteCategory
{
    public function __construct(
        protected CategoryRepository $categoryRepository
    ) {}

    public function __invoke(Category $category): bool
    {
        $result = $this->categoryRepository->delete($category);
        $this->categoryRepository->clearCache();

        return $result;
    }
}
