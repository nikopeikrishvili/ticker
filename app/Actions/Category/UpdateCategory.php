<?php

namespace App\Actions\Category;

use App\DTOs\UpdateCategoryData;
use App\Models\Category;
use App\Repositories\CategoryRepository;

class UpdateCategory
{
    public function __construct(
        protected CategoryRepository $categoryRepository
    ) {}

    public function __invoke(Category $category, UpdateCategoryData $data): Category
    {
        $this->categoryRepository->update($category, $data->toArray());

        return $category->fresh();
    }
}
