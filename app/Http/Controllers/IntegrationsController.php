<?php

namespace App\Http\Controllers;

use App\Http\Requests\Integration\SyncJiraTaskRequest;
use App\Services\JiraSync;

class IntegrationsController extends Controller
{
    /**
     * Test Jira connection.
     */
    public function testJiraConnection(JiraSync $jira)
    {
        if (!$jira->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Jira is not configured',
            ], 400);
        }

        $connected = $jira->testConnection();

        return response()->json([
            'success' => $connected,
            'message' => $connected ? 'Connection successful' : 'Connection failed',
        ]);
    }

    /**
     * Sync all Jira tasks.
     */
    public function syncJira(JiraSync $jira)
    {
        if (!$jira->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Jira is not configured',
            ], 400);
        }

        $result = $jira->sync();

        return response()->json([
            'success' => empty($result['errors']),
            'synced' => $result['synced'],
            'created' => $result['created'],
            'updated' => $result['updated'],
            'errors' => $result['errors'],
            'last_sync_at' => $jira->getLastSyncAt()?->toIso8601String(),
        ]);
    }

    /**
     * Sync a single Jira task.
     */
    public function syncJiraTask(SyncJiraTaskRequest $request, JiraSync $jira)
    {
        if (!$jira->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Jira is not configured',
            ], 400);
        }

        try {
            $todo = $jira->syncTask($request->ticket_id);

            if ($todo) {
                return response()->json([
                    'success' => true,
                    'message' => 'Task synced successfully',
                    'todo' => $todo,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found in Jira',
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Jira sync status.
     */
    public function jiraStatus(JiraSync $jira)
    {
        return response()->json([
            'configured' => $jira->isConfigured(),
            'last_sync_at' => $jira->getLastSyncAt()?->toIso8601String(),
        ]);
    }
}
