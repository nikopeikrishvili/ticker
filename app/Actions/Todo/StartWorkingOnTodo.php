<?php

namespace App\Actions\Todo;

use App\Models\TimeLog;
use App\Models\Todo;

class StartWorkingOnTodo
{
    public function __invoke(Todo $todo): TimeLog
    {
        return $todo->startWorking();
    }
}
