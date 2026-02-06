<?php

namespace App\Contracts;

use App\Models\Todo;
use Carbon\Carbon;

interface SyncTasksInterface
{
    /**
     * Get the name of the integration.
     */
    public function getName(): string;

    /**
     * Check if the integration is enabled and properly configured.
     */
    public function isConfigured(): bool;

    /**
     * Sync all tasks that have been updated since the last sync.
     *
     * @return array{synced: int, created: int, updated: int, errors: array}
     */
    public function sync(): array;

    /**
     * Sync a single task by its external ID.
     */
    public function syncTask(string $externalId): ?Todo;

    /**
     * Get the last sync timestamp.
     */
    public function getLastSyncAt(): ?Carbon;

    /**
     * Set the last sync timestamp.
     */
    public function setLastSyncAt(Carbon $date): void;

    /**
     * Test the connection to the external service.
     */
    public function testConnection(): bool;
}
