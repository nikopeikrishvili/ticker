<?php

namespace App\Actions\Todo;

use App\Repositories\SettingRepository;
use App\Repositories\TodoRepository;
use Carbon\Carbon;

class CarryOverWeekTodos
{
    public function __construct(
        protected TodoRepository $todoRepository,
        protected SettingRepository $settingRepository
    ) {}

    public function __invoke(string $weekKey): array
    {
        $timezone = $this->settingRepository->getValue('general.timezone', 'Asia/Tbilisi');

        // Parse week key to get date range
        preg_match('/(\d{4})-W(\d{2})/', $weekKey, $matches);
        $year = (int) $matches[1];
        $week = (int) $matches[2];

        $startOfWeek = Carbon::now($timezone)->setISODate($year, $week)->startOfWeek();
        $endOfWeek = $startOfWeek->copy()->addDays(4); // Friday

        // Get all incomplete todos from current week (Mon-Fri)
        $incompleteTodos = $this->todoRepository->getIncompleteForDateRange(
            $startOfWeek->format('Y-m-d'),
            $endOfWeek->format('Y-m-d')
        );

        $movedCount = 0;

        foreach ($incompleteTodos as $todo) {
            // Move to same day of week in next week (add 7 days)
            $newDate = Carbon::parse($todo->todo_date)->addWeek();
            $this->todoRepository->update($todo, [
                'todo_date' => $newDate->format('Y-m-d'),
            ]);
            $movedCount++;
        }

        // Calculate next week key
        $nextWeekKey = Carbon::now($timezone)->setISODate($year, $week)->addWeek()->format('Y-\\WW');

        return [
            'moved_count' => $movedCount,
            'next_week_key' => $nextWeekKey,
        ];
    }
}
