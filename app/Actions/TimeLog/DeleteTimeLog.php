<?php

namespace App\Actions\TimeLog;

use App\Models\TimeLog;
use App\Repositories\TimeLogRepository;

class DeleteTimeLog
{
    public function __construct(
        protected TimeLogRepository $timeLogRepository
    ) {}

    public function __invoke(TimeLog $timeLog): bool
    {
        return $this->timeLogRepository->delete($timeLog);
    }
}
