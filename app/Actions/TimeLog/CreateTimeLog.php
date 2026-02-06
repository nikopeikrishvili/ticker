<?php

namespace App\Actions\TimeLog;

use App\DTOs\CreateTimeLogData;
use App\Models\TimeLog;
use App\Repositories\CategoryRepository;
use App\Repositories\TimeLogRepository;

class CreateTimeLog
{
    public function __construct(
        protected TimeLogRepository $timeLogRepository,
        protected CategoryRepository $categoryRepository
    ) {}

    public function __invoke(CreateTimeLogData $data): TimeLog
    {
        $finalData = $data;

        // Auto-detect category from description if not provided
        if ($data->categoryId === null && $data->description !== null) {
            $detectedCategory = $this->categoryRepository->detectFromText($data->description);
            if ($detectedCategory) {
                $finalData = $data->withCategoryId($detectedCategory->id);
            }
        }

        $timeLog = $this->timeLogRepository->create($finalData->toArray());
        $timeLog->load(['todo', 'category']);

        return $timeLog;
    }
}
