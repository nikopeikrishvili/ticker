<?php

namespace App\Http\Controllers;

use App\Actions\Todo\AssignTodoToDay;
use App\Actions\Todo\CarryOverWeekTodos;
use App\Actions\Todo\MoveTodoToBacklog;
use App\Http\Requests\WeeklyPlanner\AssignToDayRequest;
use App\Http\Requests\WeeklyPlanner\CarryOverWeekRequest;
use App\Http\Requests\WeeklyPlanner\MoveToBacklogRequest;
use App\Http\Requests\WeeklyPlanner\ReorderTasksRequest;
use App\Models\Todo;
use App\Repositories\SettingRepository;
use App\Repositories\TaskPlacementRepository;
use App\Repositories\TodoRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class WeeklyPlannerController extends Controller
{
    public function __construct(
        protected TodoRepository $todoRepository,
        protected TaskPlacementRepository $taskPlacementRepository,
        protected SettingRepository $settingRepository
    ) {}

    /**
     * Get Carbon instance with user's timezone.
     */
    private function now(): Carbon
    {
        $timezone = $this->settingRepository->getValue('general.timezone', 'Asia/Tbilisi');
        return Carbon::now($timezone);
    }

    /**
     * Display the weekly planner page.
     */
    public function show(?string $weekKey = null): Response
    {
        $weekKey = $weekKey ?? $this->getCurrentWeekKey();

        return Inertia::render('WeeklyPlanner', [
            'initialWeekKey' => $weekKey,
        ]);
    }

    /**
     * Get week data (5 days + backlog).
     */
    public function index(Request $request): JsonResponse
    {
        $weekKey = $request->query('week', $this->getCurrentWeekKey());

        // Parse week key to get date range
        preg_match('/(\d{4})-W(\d{2})/', $weekKey, $matches);
        $year = (int) $matches[1];
        $week = (int) $matches[2];

        $startOfWeek = $this->now()->setISODate($year, $week)->startOfWeek(); // Monday

        // Organize by day (1 = Monday, 5 = Friday)
        $days = [];
        for ($day = 1; $day <= 5; $day++) {
            $date = $startOfWeek->copy()->addDays($day - 1);
            $todos = $this->todoRepository->getForDate($date->format('Y-m-d'))
                ->map(fn ($todo) => $this->formatTodo($todo));

            $days[$day] = [
                'date' => $date->format('Y-m-d'),
                'current' => $todos,
                'ghosts' => [],
            ];
        }

        // Get backlog todos (no date assigned, incomplete)
        $backlog = $this->todoRepository->getBacklog()
            ->map(fn ($todo) => $this->formatTodo($todo));

        // Get previous and next week keys
        $prevWeekKey = $this->getAdjacentWeekKey($weekKey, -1);
        $nextWeekKey = $this->getAdjacentWeekKey($weekKey, 1);

        return response()->json([
            'weekKey' => $weekKey,
            'weekDisplay' => $this->formatWeekDisplay($weekKey),
            'prevWeekKey' => $prevWeekKey,
            'nextWeekKey' => $nextWeekKey,
            'isCurrentWeek' => $weekKey === $this->getCurrentWeekKey(),
            'days' => $days,
            'backlog' => $backlog,
        ]);
    }

    /**
     * Assign a todo to a specific day.
     */
    public function assignToDay(AssignToDayRequest $request, AssignTodoToDay $assignTodoToDay): JsonResponse
    {
        $todo = $this->todoRepository->findOrFail($request->todo_id);
        $todo = $assignTodoToDay($todo, $request->week_key, $request->day_of_week);

        return response()->json([
            'success' => true,
            'todo' => $this->formatTodo($todo),
        ]);
    }

    /**
     * Move a todo back to backlog.
     */
    public function moveToBacklog(MoveToBacklogRequest $request, MoveTodoToBacklog $moveTodoToBacklog): JsonResponse
    {
        $todo = $this->todoRepository->findOrFail($request->todo_id);
        $todo = $moveTodoToBacklog($todo);

        return response()->json([
            'success' => true,
            'todo' => $this->formatTodo($todo),
        ]);
    }

    /**
     * Reorder tasks within a day.
     */
    public function reorder(ReorderTasksRequest $request): JsonResponse
    {
        foreach ($request->placements as $placementData) {
            $this->taskPlacementRepository->updateOrder($placementData['id'], $placementData['order']);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Carry over incomplete tasks to next week.
     */
    public function carryOverToNextWeek(CarryOverWeekRequest $request, CarryOverWeekTodos $carryOverWeekTodos): JsonResponse
    {
        $result = $carryOverWeekTodos($request->week_key);

        return response()->json([
            'success' => true,
            'movedCount' => $result['moved_count'],
            'nextWeekKey' => $result['next_week_key'],
        ]);
    }

    /**
     * Get the current week key in ISO format.
     */
    private function getCurrentWeekKey(): string
    {
        return $this->now()->format('Y-\\WW');
    }

    /**
     * Get adjacent week key.
     */
    private function getAdjacentWeekKey(string $weekKey, int $offset): string
    {
        // Parse week key (e.g., "2026-W05")
        preg_match('/(\d{4})-W(\d{2})/', $weekKey, $matches);
        $year = (int) $matches[1];
        $week = (int) $matches[2];

        $date = $this->now()->setISODate($year, $week)->addWeeks($offset);
        return $date->format('Y-\\WW');
    }

    /**
     * Format week display string in Georgian.
     */
    private function formatWeekDisplay(string $weekKey): string
    {
        preg_match('/(\d{4})-W(\d{2})/', $weekKey, $matches);
        $year = (int) $matches[1];
        $week = (int) $matches[2];

        $startOfWeek = $this->now()->setISODate($year, $week)->startOfWeek();
        $endOfWeek = $startOfWeek->copy()->endOfWeek()->subDays(2); // Friday

        $months = [
            1 => 'იანვარი', 2 => 'თებერვალი', 3 => 'მარტი', 4 => 'აპრილი',
            5 => 'მაისი', 6 => 'ივნისი', 7 => 'ივლისი', 8 => 'აგვისტო',
            9 => 'სექტემბერი', 10 => 'ოქტომბერი', 11 => 'ნოემბერი', 12 => 'დეკემბერი',
        ];

        $startMonth = $months[$startOfWeek->month];
        $endMonth = $months[$endOfWeek->month];

        if ($startOfWeek->month === $endOfWeek->month) {
            return "{$startOfWeek->day}-{$endOfWeek->day} {$startMonth}, {$year}";
        }

        return "{$startOfWeek->day} {$startMonth} - {$endOfWeek->day} {$endMonth}, {$year}";
    }

    /**
     * Format todo for JSON response.
     */
    private function formatTodo(Todo $todo): array
    {
        return [
            'id' => $todo->id,
            'content' => $todo->content,
            'status' => $todo->status,
            'status_label' => $todo->status_label,
            'priority' => $todo->priority,
            'is_completed' => $todo->is_completed,
            'is_working' => $todo->is_working,
            'display_id' => $todo->display_id,
            'order' => $todo->order,
            'current_week_key' => $todo->current_week_key,
            'current_day' => $todo->current_day,
            'todo_date' => $todo->todo_date?->format('Y-m-d'),
            'jira_key' => $todo->jira_key,
            'jira_url' => $todo->jira_url,
        ];
    }
}
