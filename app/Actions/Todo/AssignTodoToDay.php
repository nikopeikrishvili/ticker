<?php

namespace App\Actions\Todo;

use App\Models\Todo;
use App\Repositories\SettingRepository;
use App\Repositories\TodoRepository;
use Carbon\Carbon;

class AssignTodoToDay
{
    public function __construct(
        protected TodoRepository $todoRepository,
        protected SettingRepository $settingRepository
    ) {}

    public function __invoke(Todo $todo, string $weekKey, int $dayOfWeek): Todo
    {
        $date = $this->calculateDateFromWeekAndDay($weekKey, $dayOfWeek);

        $this->todoRepository->update($todo, [
            'todo_date' => $date->format('Y-m-d'),
        ]);

        return $todo->fresh();
    }

    private function calculateDateFromWeekAndDay(string $weekKey, int $dayOfWeek): Carbon
    {
        preg_match('/(\d{4})-W(\d{2})/', $weekKey, $matches);
        $year = (int) $matches[1];
        $week = (int) $matches[2];

        $timezone = $this->settingRepository->getValue('general.timezone', 'Asia/Tbilisi');

        return Carbon::now($timezone)->setISODate($year, $week)->startOfWeek()->addDays($dayOfWeek - 1);
    }
}
