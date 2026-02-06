<?php

namespace App\Actions\RecurringTask;

use App\DTOs\UpdateRecurringTaskData;
use App\Models\RecurringTask;
use App\Repositories\RecurringTaskRepository;

class UpdateRecurringTask
{
    public function __construct(
        protected RecurringTaskRepository $recurringTaskRepository
    ) {}

    public function __invoke(RecurringTask $recurringTask, UpdateRecurringTaskData $data): RecurringTask
    {
        $this->recurringTaskRepository->update($recurringTask, $data->toArray());

        return $recurringTask->fresh();
    }
}
