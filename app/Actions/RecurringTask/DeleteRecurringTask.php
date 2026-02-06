<?php

namespace App\Actions\RecurringTask;

use App\Models\RecurringTask;
use App\Repositories\RecurringTaskRepository;

class DeleteRecurringTask
{
    public function __construct(
        protected RecurringTaskRepository $recurringTaskRepository
    ) {}

    public function __invoke(RecurringTask $recurringTask): bool
    {
        return $this->recurringTaskRepository->delete($recurringTask);
    }
}
