<?php

namespace App\Actions\Category;

use App\DTOs\CreateCategoryData;
use App\Models\Category;
use App\Repositories\CategoryRepository;

class CreateCategory
{
    public function __construct(
        protected CategoryRepository $categoryRepository
    ) {}

    public function __invoke(CreateCategoryData $data): Category
    {
        $attributes = $data->toArray();
        $attributes['order'] = $this->categoryRepository->getNextOrder();

        $category = $this->categoryRepository->create($attributes);
        $this->categoryRepository->clearCache();

        return $category;
    }
}
