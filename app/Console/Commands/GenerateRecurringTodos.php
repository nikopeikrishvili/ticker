<?php

namespace App\Console\Commands;

use App\Models\RecurringTask;
use App\Models\Todo;
use Carbon\Carbon;
use Illuminate\Console\Command;

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
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::today();

        $this->info("Generating recurring todos for {$date->format('Y-m-d')}...");

        $recurringTasks = RecurringTask::active()->get();
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
            $exists = Todo::where('todo_date', $date->toDateString())
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
                'todo_date' => $date->toDateString(),
                'content' => $task->content,
                'is_completed' => false,
                'order' => Todo::max('order') + 1,
            ]);

            // Update last generated date
            $task->update(['last_generated_date' => $date]);

            $this->info("  Created: {$task->content}");
            $created++;
        }

        $this->newLine();
        $this->info("Done! Created: {$created}, Skipped: {$skipped}");

        return Command::SUCCESS;
    }
}
