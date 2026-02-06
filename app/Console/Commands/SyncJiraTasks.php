<?php

namespace App\Console\Commands;

use App\Services\JiraSync;
use Illuminate\Console\Command;

class SyncJiraTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:jira
                            {--task= : Sync a specific task by Jira key (e.g., PROJECT-123)}
                            {--test : Test the connection without syncing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync tasks from Jira';

    /**
     * Execute the console command.
     */
    public function handle(JiraSync $jira): int
    {
        if (!$jira->isConfigured()) {
            $this->error('Jira integration is not configured. Please configure it in Settings > Integrations.');
            return self::FAILURE;
        }

        // Test connection only
        if ($this->option('test')) {
            $this->info('Testing Jira connection...');

            if ($jira->testConnection()) {
                $this->info('Connection successful!');
                return self::SUCCESS;
            } else {
                $this->error('Connection failed. Please check your credentials.');
                return self::FAILURE;
            }
        }

        // Sync specific task
        $taskKey = $this->option('task');
        if ($taskKey) {
            $this->info("Syncing task: {$taskKey}");

            try {
                $todo = $jira->syncTask($taskKey);

                if ($todo) {
                    $this->info("Task synced successfully: {$todo->content}");
                    return self::SUCCESS;
                } else {
                    $this->error("Task not found: {$taskKey}");
                    return self::FAILURE;
                }
            } catch (\Exception $e) {
                $this->error("Failed to sync task: {$e->getMessage()}");
                return self::FAILURE;
            }
        }

        // Full sync
        $this->info('Starting Jira sync...');

        $lastSync = $jira->getLastSyncAt();
        if ($lastSync) {
            $this->info("Last sync: {$lastSync->format('Y-m-d H:i:s')}");
        } else {
            $this->info('This is the first sync');
        }

        $result = $jira->sync();

        $this->newLine();
        $this->info("Sync completed:");
        $this->line("  - Total synced: {$result['synced']}");
        $this->line("  - Created: {$result['created']}");
        $this->line("  - Updated: {$result['updated']}");

        if (!empty($result['errors'])) {
            $this->newLine();
            $this->warn("Errors encountered:");
            foreach ($result['errors'] as $error) {
                $this->error("  - {$error}");
            }
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
