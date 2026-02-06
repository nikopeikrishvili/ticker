<?php

namespace App\Services;

use App\Contracts\SyncTasksInterface;
use App\Models\Setting;
use App\Models\Todo;
use App\Repositories\SettingRepository;
use App\Repositories\TodoRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JiraSync implements SyncTasksInterface
{
    protected ?string $baseUrl = null;
    protected ?string $email = null;
    protected ?string $apiToken = null;
    protected ?string $projectKey = null;
    protected ?int $userId = null;
    protected SettingRepository $settingRepository;
    protected TodoRepository $todoRepository;

    public function __construct()
    {
        $this->userId = auth()->id();
        $this->settingRepository = app(SettingRepository::class);
        $this->todoRepository = app(TodoRepository::class);
        $this->loadConfig();
    }

    /**
     * Load configuration from settings.
     */
    protected function loadConfig(): void
    {
        $this->baseUrl = rtrim($this->settingRepository->getValue('integrations.jira.url', ''), '/');
        $this->email = $this->settingRepository->getValue('integrations.jira.email', '');
        $this->apiToken = $this->settingRepository->getValue('integrations.jira.api_token', '');
        $this->projectKey = $this->settingRepository->getValue('integrations.jira.project_key', '');
    }

    /**
     * Get the name of the integration.
     */
    public function getName(): string
    {
        return 'Jira';
    }

    /**
     * Check if the integration is enabled and properly configured.
     */
    public function isConfigured(): bool
    {
        $enabled = $this->settingRepository->getValue('integrations.jira.enabled', 'false') === 'true';

        return $enabled
            && !empty($this->baseUrl)
            && !empty($this->email)
            && !empty($this->apiToken)
            && !empty($this->projectKey);
    }

    /**
     * Sync all tasks that have been updated since the last sync.
     */
    public function sync(): array
    {
        $result = [
            'synced' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => [],
        ];

        if (!$this->isConfigured()) {
            $result['errors'][] = 'Jira integration is not configured';
            return $result;
        }

        try {
            $lastSync = $this->getLastSyncAt();
            $issues = $this->fetchUpdatedIssues($lastSync);

            foreach ($issues as $issue) {
                try {
                    $wasCreated = $this->syncIssue($issue);
                    $result['synced']++;
                    if ($wasCreated) {
                        $result['created']++;
                    } else {
                        $result['updated']++;
                    }
                } catch (\Exception $e) {
                    $result['errors'][] = "Failed to sync {$issue['key']}: {$e->getMessage()}";
                    Log::error("Jira sync error for {$issue['key']}", ['error' => $e->getMessage()]);
                }
            }

            // Update last sync time
            $this->setLastSyncAt(now());

        } catch (\Exception $e) {
            $result['errors'][] = "Sync failed: {$e->getMessage()}";
            Log::error('Jira sync failed', ['error' => $e->getMessage()]);
        }

        return $result;
    }

    /**
     * Sync a single task by its Jira key.
     */
    public function syncTask(string $externalId): ?Todo
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Jira integration is not configured');
        }

        $issue = $this->fetchIssue($externalId);

        if (!$issue) {
            return null;
        }

        $this->syncIssue($issue);

        return $this->todoRepository->findByJiraKey($externalId);
    }

    /**
     * Get the last sync timestamp.
     */
    public function getLastSyncAt(): ?Carbon
    {
        $value = $this->settingRepository->getValue('integrations.jira.last_sync_at', '');

        if (empty($value)) {
            return null;
        }

        return Carbon::parse($value);
    }

    /**
     * Set the last sync timestamp.
     */
    public function setLastSyncAt(Carbon $date): void
    {
        $this->settingRepository->setValue('integrations.jira.last_sync_at', $date->toIso8601String());
    }

    /**
     * Test the connection to Jira.
     */
    public function testConnection(): bool
    {
        if (empty($this->baseUrl) || empty($this->email) || empty($this->apiToken)) {
            return false;
        }

        try {
            $response = Http::withBasicAuth($this->email, $this->apiToken)
                ->timeout(10)
                ->get("{$this->baseUrl}/rest/api/3/myself");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Jira connection test failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Fetch issues updated since the given date.
     */
    protected function fetchUpdatedIssues(?Carbon $since = null): array
    {
        $jql = "project = {$this->projectKey}";

        if ($since) {
            $jql .= " AND updated >= '{$since->format('Y-m-d H:i')}'";
        }

        $jql .= ' ORDER BY updated DESC';

        $response = Http::withBasicAuth($this->email, $this->apiToken)
            ->timeout(30)
            ->get("{$this->baseUrl}/rest/api/3/search", [
                'jql' => $jql,
                'fields' => 'summary,status,assignee,updated,created',
                'maxResults' => 100,
            ]);

        if (!$response->successful()) {
            throw new \Exception("Jira API error: {$response->status()} - {$response->body()}");
        }

        return $response->json('issues', []);
    }

    /**
     * Fetch a single issue by key.
     */
    protected function fetchIssue(string $issueKey): ?array
    {
        $response = Http::withBasicAuth($this->email, $this->apiToken)
            ->timeout(30)
            ->get("{$this->baseUrl}/rest/api/3/issue/{$issueKey}", [
                'fields' => 'summary,status,assignee,updated,created',
            ]);

        if ($response->status() === 404) {
            return null;
        }

        if (!$response->successful()) {
            throw new \Exception("Jira API error: {$response->status()} - {$response->body()}");
        }

        return $response->json();
    }

    /**
     * Sync a single Jira issue to the database.
     *
     * @return bool True if created, false if updated
     */
    protected function syncIssue(array $issue): bool
    {
        $key = $issue['key'];
        $fields = $issue['fields'];

        $todo = $this->todoRepository->findByJiraKey($key);
        $wasCreated = false;

        $status = $this->mapJiraStatus($fields['status']['name'] ?? 'To Do');

        if (!$todo) {
            $timezone = $this->settingRepository->getValue('general.timezone', 'Asia/Tbilisi');
            $todo = $this->todoRepository->create([
                'jira_key' => $key,
                'jira_url' => "{$this->baseUrl}/browse/{$key}",
                'todo_date' => now()->setTimezone($timezone)->toDateString(),
                'content' => $fields['summary'] ?? $key,
                'status' => $status,
                'is_completed' => $status === Todo::STATUS_DONE,
                'order' => $this->todoRepository->getNextOrder(),
            ]);
            $wasCreated = true;
        } else {
            $this->todoRepository->update($todo, [
                'content' => $fields['summary'] ?? $key,
                'status' => $status,
                'is_completed' => $status === Todo::STATUS_DONE,
            ]);
        }

        return $wasCreated;
    }

    /**
     * Map Jira status to local status.
     */
    protected function mapJiraStatus(string $jiraStatus): string
    {
        $statusLower = strtolower($jiraStatus);

        // Map common Jira statuses
        $mapping = [
            'to do' => Todo::STATUS_TODO,
            'open' => Todo::STATUS_TODO,
            'backlog' => Todo::STATUS_BACKLOG,
            'in progress' => Todo::STATUS_IN_PROGRESS,
            'in review' => Todo::STATUS_IN_PROGRESS,
            'review' => Todo::STATUS_IN_PROGRESS,
            'done' => Todo::STATUS_DONE,
            'closed' => Todo::STATUS_DONE,
            'resolved' => Todo::STATUS_DONE,
        ];

        return $mapping[$statusLower] ?? Todo::STATUS_TODO;
    }
}
