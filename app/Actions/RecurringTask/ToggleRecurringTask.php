<?php

namespace App\Actions\RecurringTask;

use App\Models\RecurringTask;
use App\Repositories\RecurringTaskRepository;

class ToggleRecurringTask
{
    public function __construct(
        protected RecurringTaskRepository $recurringTaskRepository
    ) {}

    public function __invoke(RecurringTask $recurringTask): RecurringTask
    {
        return $this->recurringTaskRepository->toggle($recurringTask);
    }
}
