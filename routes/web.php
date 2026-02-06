<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\TimeLogController;
use App\Http\Controllers\WeeklyPlannerController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\RecurringTaskController;
use App\Http\Controllers\IntegrationsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Guest routes (login/register)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Main notebook page (Inertia route)
    Route::get('/', function () {
        return Inertia::render('Notebook');
    });

    // Weekly planner pages
    Route::get('/weekly', [WeeklyPlannerController::class, 'show']);
    Route::get('/weekly/{weekKey}', [WeeklyPlannerController::class, 'show']);

    // Todo API routes
    Route::prefix('api/todos')->group(function () {
        Route::get('/', [TodoController::class, 'index']);
        Route::get('/statuses', [TodoController::class, 'statuses']);
        Route::get('/pending-previous', [TodoController::class, 'pendingFromPreviousDates']);
        Route::post('/', [TodoController::class, 'store']);
        Route::post('/carry-over', [TodoController::class, 'carryOver']);
        Route::put('/{todo}', [TodoController::class, 'update']);
        Route::delete('/{todo}', [TodoController::class, 'destroy']);
        Route::post('/{todo}/start', [TodoController::class, 'startWorking']);
        Route::post('/{todo}/stop', [TodoController::class, 'stopWorking']);
    });

    // Time Log API routes
    Route::prefix('api/time-logs')->group(function () {
        Route::get('/', [TimeLogController::class, 'index']);
        Route::get('/categories', [TimeLogController::class, 'categories']);
        Route::post('/categories', [TimeLogController::class, 'storeCategory']);
        Route::put('/categories/{category}', [TimeLogController::class, 'updateCategory']);
        Route::delete('/categories/{category}', [TimeLogController::class, 'destroyCategory']);
        Route::post('/', [TimeLogController::class, 'store']);
        Route::put('/{timeLog}', [TimeLogController::class, 'update']);
        Route::delete('/{timeLog}', [TimeLogController::class, 'destroy']);
    });

    // Weekly Planner API routes
    Route::prefix('api/weekly')->group(function () {
        Route::get('/', [WeeklyPlannerController::class, 'index']);
        Route::post('/assign', [WeeklyPlannerController::class, 'assignToDay']);
        Route::post('/backlog', [WeeklyPlannerController::class, 'moveToBacklog']);
        Route::post('/reorder', [WeeklyPlannerController::class, 'reorder']);
        Route::post('/carry-over', [WeeklyPlannerController::class, 'carryOverToNextWeek']);
    });

    // Settings API routes
    Route::prefix('api/settings')->group(function () {
        Route::get('/', [SettingsController::class, 'index']);
        Route::get('/category/{category}', [SettingsController::class, 'category']);
        Route::post('/', [SettingsController::class, 'update']);
        Route::post('/batch', [SettingsController::class, 'updateBatch']);
        Route::post('/reset', [SettingsController::class, 'reset']);
        Route::post('/reset-all', [SettingsController::class, 'resetAll']);
    });

    // Recurring Tasks API routes
    Route::prefix('api/recurring-tasks')->group(function () {
        Route::get('/', [RecurringTaskController::class, 'index']);
        Route::post('/', [RecurringTaskController::class, 'store']);
        Route::put('/{recurringTask}', [RecurringTaskController::class, 'update']);
        Route::delete('/{recurringTask}', [RecurringTaskController::class, 'destroy']);
        Route::post('/{recurringTask}/toggle', [RecurringTaskController::class, 'toggle']);
    });

    // Integrations API routes
    Route::prefix('api/integrations')->group(function () {
        // Jira
        Route::get('/jira/status', [IntegrationsController::class, 'jiraStatus']);
        Route::post('/jira/test', [IntegrationsController::class, 'testJiraConnection']);
        Route::post('/jira/sync', [IntegrationsController::class, 'syncJira']);
        Route::post('/jira/sync-task', [IntegrationsController::class, 'syncJiraTask']);
    });
});
