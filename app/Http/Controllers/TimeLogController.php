<?php

namespace App\Http\Controllers;

use App\Actions\Category\CreateCategory;
use App\Actions\Category\DeleteCategory;
use App\Actions\Category\UpdateCategory;
use App\Actions\TimeLog\CreateTimeLog;
use App\Actions\TimeLog\DeleteTimeLog;
use App\Actions\TimeLog\UpdateTimeLog;
use App\DTOs\CreateCategoryData;
use App\DTOs\CreateTimeLogData;
use App\DTOs\UpdateCategoryData;
use App\DTOs\UpdateTimeLogData;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Requests\TimeLog\StoreTimeLogRequest;
use App\Http\Requests\TimeLog\UpdateTimeLogRequest;
use App\Models\Category;
use App\Models\TimeLog;
use App\Repositories\CategoryRepository;
use App\Repositories\SettingRepository;
use App\Repositories\TimeLogRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TimeLogController extends Controller
{
    public function __construct(
        protected TimeLogRepository $timeLogRepository,
        protected CategoryRepository $categoryRepository,
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
            $this->timeLogRepository->getForDate($date)->map(function ($log) {
                return $this->formatTimeLog($log);
            })
        );
    }

    public function categories()
    {
        return response()->json(
            $this->categoryRepository->getAllOrdered()->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'icon' => $cat->icon,
                    'color' => $cat->color,
                    'keywords' => $cat->keywords,
                    'order' => $cat->order,
                ];
            })
        );
    }

    public function storeCategory(StoreCategoryRequest $request, CreateCategory $createCategory)
    {
        $data = CreateCategoryData::fromRequest($request);
        $category = $createCategory($data);

        return response()->json([
            'id' => $category->id,
            'name' => $category->name,
            'icon' => $category->icon,
            'color' => $category->color,
            'keywords' => $category->keywords,
            'order' => $category->order,
        ], 201);
    }

    public function updateCategory(UpdateCategoryRequest $request, Category $category, UpdateCategory $updateCategory)
    {
        if (!$this->categoryRepository->belongsToUser($category)) {
            abort(403);
        }

        $data = UpdateCategoryData::fromRequest($request);
        $category = $updateCategory($category, $data);

        return response()->json([
            'id' => $category->id,
            'name' => $category->name,
            'icon' => $category->icon,
            'color' => $category->color,
            'keywords' => $category->keywords,
            'order' => $category->order,
        ]);
    }

    public function destroyCategory(Category $category, DeleteCategory $deleteCategory)
    {
        if (!$this->categoryRepository->belongsToUser($category)) {
            abort(403);
        }

        $deleteCategory($category);

        return response()->json(null, 204);
    }

    public function store(StoreTimeLogRequest $request, CreateTimeLog $createTimeLog)
    {
        $data = CreateTimeLogData::fromRequest($request, $this->getCurrentDate());
        $timeLog = $createTimeLog($data);

        return response()->json($this->formatTimeLog($timeLog), 201);
    }

    public function update(UpdateTimeLogRequest $request, TimeLog $timeLog, UpdateTimeLog $updateTimeLog)
    {
        if (!$this->timeLogRepository->belongsToUser($timeLog)) {
            abort(403);
        }

        $data = UpdateTimeLogData::fromRequest($request);
        $timeLog = $updateTimeLog($timeLog, $data);

        return response()->json($this->formatTimeLog($timeLog));
    }

    public function destroy(TimeLog $timeLog, DeleteTimeLog $deleteTimeLog)
    {
        if (!$this->timeLogRepository->belongsToUser($timeLog)) {
            abort(403);
        }

        $deleteTimeLog($timeLog);

        return response()->json(null, 204);
    }

    private function formatTimeLog(TimeLog $log): array
    {
        return [
            'id' => $log->id,
            'todo_id' => $log->todo_id,
            'task_id' => $log->todo?->display_id,
            'category_id' => $log->category_id,
            'category' => $log->category ? [
                'id' => $log->category->id,
                'name' => $log->category->name,
                'icon' => $log->category->icon,
                'color' => $log->category->color,
            ] : null,
            'log_date' => $log->log_date?->format('Y-m-d'),
            'start_time' => $log->start_time?->format('H:i'),
            'end_time' => $log->end_time?->format('H:i'),
            'description' => $log->description,
            'duration_minutes' => $log->duration_minutes,
            'formatted_duration' => $log->formatted_duration,
            'created_at' => $log->created_at,
            'updated_at' => $log->updated_at,
        ];
    }
}
