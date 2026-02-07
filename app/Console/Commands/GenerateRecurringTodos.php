<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\Todo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class GenerateRecurringTodos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'todos:generate-recurring {--date= : The date to generate todos for (default: today)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate todos from recurring task templates';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $users = User::whereHas('recurringTasks', fn($q) => $q->where('is_active', true))->get();

        $totalCreated = 0;
        $totalSkipped = 0;

        foreach ($users as $user) {
            Auth::loginUsingId($user->id);

            $timezone = Setting::getValue('general.timezone', 'Asia/Tbilisi', $user->id);
            $date = $this->option('date')
                ? Carbon::parse($this->option('date'))
                : Carbon::now($timezone)->startOfDay();

            $this->info("Processing user #{$user->id} ({$user->name}) for {$date->format('Y-m-d')}...");

            $recurringTasks = $user->recurringTasks()->where('is_active', true)->get();
            $created = 0;
            $skipped = 0;

            foreach ($recurringTasks as $task) {
                // Check if task should run on this date
                if (!$task->shouldRunOn($date)) {
                    $this->line("  Skipped (not scheduled): {$task->content}");
                    $skipped++;
                    continue;
                }

                // Check if already generated for this date
                if ($task->last_generated_date && $task->last_generated_date->eq($date)) {
                    $this->line("  Skipped (already generated): {$task->content}");
                    $skipped++;
                    continue;
                }

                // Check if todo already exists for this date with same content
                $exists = Todo::where('user_id', $user->id)
                    ->where('todo_date', $date->toDateString())
                    ->where('content', $task->content)
                    ->exists();

                if ($exists) {
                    $this->line("  Skipped (todo exists): {$task->content}");
                    $task->update(['last_generated_date' => $date]);
                    $skipped++;
                    continue;
                }

                // Create the todo
                Todo::create([
                    'user_id' => $user->id,
                    'todo_date' => $date->toDateString(),
                    'content' => $task->content,
                    'is_completed' => false,
                    'order' => Todo::where('user_id', $user->id)->max('order') + 1,
                ]);

                // Update last generated date
                $task->update(['last_generated_date' => $date]);

                $this->info("  Created: {$task->content}");
                $created++;
            }

            $totalCreated += $created;
            $totalSkipped += $skipped;
        }

        Auth::logout();

        $this->newLine();
        $this->info("Done! Created: {$totalCreated}, Skipped: {$totalSkipped}");

        return Command::SUCCESS;
    }
}
