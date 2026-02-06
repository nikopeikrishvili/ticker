<?php

namespace App\Http\Controllers;

use App\Actions\Todo\CarryOverTodos;
use App\Actions\Todo\CreateTodo;
use App\Actions\Todo\DeleteTodo;
use App\Actions\Todo\StartWorkingOnTodo;
use App\Actions\Todo\StopWorkingOnTodo;
use App\Actions\Todo\UpdateTodo;
use App\DTOs\CreateTodoData;
use App\DTOs\UpdateTodoData;
use App\Http\Requests\Todo\CarryOverTodosRequest;
use App\Http\Requests\Todo\StoreTodoRequest;
use App\Http\Requests\Todo\UpdateTodoRequest;
use App\Models\Todo;
use App\Repositories\SettingRepository;
use App\Repositories\TodoRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TodoController extends Controller
{
    public function __construct(
        protected TodoRepository $todoRepository,
        protected SettingRepository $settingRepository
    ) {}

    private function getCurrentDate(): string
    {
        $timezone = $this->settingRepository->getValue('general.timezone', 'Asia/Tbilisi');
        return Carbon::now($timezone)->toDateString();
    }

    public function index(Request $request)
    {
        $date = $request->query('date', $this->getCurrentDate());

        return response()->json(
            $this->todoRepository->getForDate($date)
        );
    }

    public function store(StoreTodoRequest $request, CreateTodo $createTodo)
    {
        $data = CreateTodoData::fromRequest($request, $this->getCurrentDate());
        $todo = $createTodo($data);

        return response()->json($todo, 201);
    }

    public function update(UpdateTodoRequest $request, Todo $todo, UpdateTodo $updateTodo)
    {
        if (!$this->todoRepository->belongsToUser($todo)) {
            abort(403);
        }

        $data = UpdateTodoData::fromRequest($request);
        $todo = $updateTodo($todo, $data);

        return response()->json($todo);
    }

    public function destroy(Todo $todo, DeleteTodo $deleteTodo)
    {
        if (!$this->todoRepository->belongsToUser($todo)) {
            abort(403);
        }

        $deleteTodo($todo);

        return response()->json(null, 204);
    }

    public function startWorking(Todo $todo, StartWorkingOnTodo $startWorking)
    {
        if (!$this->todoRepository->belongsToUser($todo)) {
            abort(403);
        }

        $timeLog = $startWorking($todo);

        return response()->json([
            'todo' => $todo->fresh(),
            'time_log' => $timeLog,
        ]);
    }

    public function stopWorking(Todo $todo, StopWorkingOnTodo $stopWorking)
    {
        if (!$this->todoRepository->belongsToUser($todo)) {
            abort(403);
        }

        $timeLog = $stopWorking($todo);

        return response()->json([
            'todo' => $todo->fresh(),
            'time_log' => $timeLog,
        ]);
    }

    public function statuses()
    {
        return response()->json(Todo::$statuses);
    }

    public function pendingFromPreviousDates(Request $request)
    {
        $date = $request->query('date', $this->getCurrentDate());

        return response()->json(
            $this->todoRepository->getPendingFromPreviousDates($date)
        );
    }

    public function carryOver(CarryOverTodosRequest $request, CarryOverTodos $carryOver)
    {
        $validated = $request->validated();

        $result = $carryOver($validated['task_ids'], $validated['target_date']);

        return response()->json([
            'success' => true,
            'moved_count' => $result['moved_count'],
            'tasks' => $result['tasks'],
        ]);
    }
}
