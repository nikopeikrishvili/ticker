<?php

namespace App\Actions\TimeLog;

use App\DTOs\UpdateTimeLogData;
use App\Models\TimeLog;
use App\Repositories\TimeLogRepository;

class UpdateTimeLog
{
    public function __construct(
        protected TimeLogRepository $timeLogRepository
    ) {}

    public function __invoke(TimeLog $timeLog, UpdateTimeLogData $data): TimeLog
    {
        $this->timeLogRepository->update($timeLog, $data->toArray());
        $timeLog->load(['todo', 'category']);

        return $timeLog;
    }
}
