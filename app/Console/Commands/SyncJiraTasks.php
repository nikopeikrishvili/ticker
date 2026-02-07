<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\JiraSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

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
    public function handle(): int
    {
        // For --test and --task flags, use the current auth context (interactive CLI usage)
        if ($this->option('test') || $this->option('task')) {
            return $this->handleSingleUser(app(JiraSync::class));
        }

        // Full sync: iterate over all users with Jira enabled
        $userIds = Setting::where('key', 'integrations.jira.enabled')
            ->where('value', 'true')
            ->pluck('user_id');

        if ($userIds->isEmpty()) {
            $this->info('No users have Jira integration enabled.');
            return self::SUCCESS;
        }

        $hasErrors = false;

        foreach ($userIds as $userId) {
            Auth::loginUsingId($userId);
            $this->info("Syncing Jira for user #{$userId}...");

            $jira = app(JiraSync::class);

            if (!$jira->isConfigured()) {
                $this->warn("  Skipped user #{$userId}: Jira not fully configured.");
                continue;
            }

            $lastSync = $jira->getLastSyncAt();
            if ($lastSync) {
                $this->info("  Last sync: {$lastSync->format('Y-m-d H:i:s')}");
            } else {
                $this->info('  This is the first sync');
            }

            $result = $jira->sync();

            $this->line("  - Total synced: {$result['synced']}");
            $this->line("  - Created: {$result['created']}");
            $this->line("  - Updated: {$result['updated']}");

            if (!empty($result['errors'])) {
                $hasErrors = true;
                $this->warn("  Errors:");
                foreach ($result['errors'] as $error) {
                    $this->error("    - {$error}");
                }
            }
        }

        Auth::logout();

        $this->newLine();
        $this->info('Jira sync completed for all users.');

        return $hasErrors ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Handle --test and --task flags for a single authenticated user.
     */
    private function handleSingleUser(JiraSync $jira): int
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
}
