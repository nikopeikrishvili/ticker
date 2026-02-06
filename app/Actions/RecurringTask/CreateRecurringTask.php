<?php

namespace App\Actions\RecurringTask;

use App\DTOs\CreateRecurringTaskData;
use App\Models\RecurringTask;
use App\Repositories\RecurringTaskRepository;

class CreateRecurringTask
{
    public function __construct(
        protected RecurringTaskRepository $recurringTaskRepository
    ) {}

    public function __invoke(CreateRecurringTaskData $data): RecurringTask
    {
        return $this->recurringTaskRepository->create($data->toArray());
    }
}
