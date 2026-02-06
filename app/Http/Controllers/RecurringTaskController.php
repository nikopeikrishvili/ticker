<?php

namespace App\Http\Controllers;

use App\Actions\RecurringTask\CreateRecurringTask;
use App\Actions\RecurringTask\DeleteRecurringTask;
use App\Actions\RecurringTask\ToggleRecurringTask;
use App\Actions\RecurringTask\UpdateRecurringTask;
use App\DTOs\CreateRecurringTaskData;
use App\DTOs\UpdateRecurringTaskData;
use App\Http\Requests\RecurringTask\StoreRecurringTaskRequest;
use App\Http\Requests\RecurringTask\UpdateRecurringTaskRequest;
use App\Models\RecurringTask;
use App\Repositories\RecurringTaskRepository;
use Illuminate\Http\JsonResponse;

class RecurringTaskController extends Controller
{
    public function __construct(
        protected RecurringTaskRepository $recurringTaskRepository
    ) {}

    /**
     * Display a listing of recurring tasks.
     */
    public function index(): JsonResponse
    {
        $tasks = $this->recurringTaskRepository->getAllOrdered()->map(function ($task) {
            return $this->formatTask($task);
        });

        return response()->json($tasks);
    }

    /**
     * Store a newly created recurring task.
     */
    public function store(StoreRecurringTaskRequest $request, CreateRecurringTask $createRecurringTask): JsonResponse
    {
        $data = CreateRecurringTaskData::fromRequest($request);
        $task = $createRecurringTask($data);

        return response()->json($this->formatTask($task), 201);
    }

    /**
     * Update the specified recurring task.
     */
    public function update(UpdateRecurringTaskRequest $request, RecurringTask $recurringTask, UpdateRecurringTask $updateRecurringTask): JsonResponse
    {
        if (!$this->recurringTaskRepository->belongsToUser($recurringTask)) {
            abort(403);
        }

        $data = UpdateRecurringTaskData::fromRequest($request);
        $task = $updateRecurringTask($recurringTask, $data);

        return response()->json($this->formatTask($task));
    }

    /**
     * Remove the specified recurring task.
     */
    public function destroy(RecurringTask $recurringTask, DeleteRecurringTask $deleteRecurringTask): JsonResponse
    {
        if (!$this->recurringTaskRepository->belongsToUser($recurringTask)) {
            abort(403);
        }

        $deleteRecurringTask($recurringTask);

        return response()->json(null, 204);
    }

    /**
     * Toggle the active status of a recurring task.
     */
    public function toggle(RecurringTask $recurringTask, ToggleRecurringTask $toggleRecurringTask): JsonResponse
    {
        if (!$this->recurringTaskRepository->belongsToUser($recurringTask)) {
            abort(403);
        }

        $task = $toggleRecurringTask($recurringTask);

        return response()->json([
            'id' => $task->id,
            'is_active' => $task->is_active,
        ]);
    }

    /**
     * Format a recurring task for JSON response.
     */
    private function formatTask(RecurringTask $task): array
    {
        return [
            'id' => $task->id,
            'content' => $task->content,
            'frequency_type' => $task->frequency_type,
            'weekdays' => $task->weekdays,
            'weekday_names' => $task->weekday_names,
            'schedule_description' => $task->schedule_description,
            'is_active' => $task->is_active,
            'last_generated_date' => $task->last_generated_date?->format('Y-m-d'),
            'created_at' => $task->created_at,
        ];
    }
}
