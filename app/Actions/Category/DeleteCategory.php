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
        return $this->categoryRepository->delete($category);
    }
}
