<?php

namespace App\Actions\Todo;

use App\Models\TimeLog;
use App\Models\Todo;

class StopWorkingOnTodo
{
    public function __invoke(Todo $todo): ?TimeLog
    {
        return $todo->stopWorking();
    }
}
