<?php

namespace Tests\Feature;

use App\Models\RecurringTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringTaskTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_unauthenticated_user_cannot_access_recurring_tasks(): void
    {
        $response = $this->getJson('/api/recurring-tasks');

        $response->assertStatus(401);
    }

    public function test_user_can_list_recurring_tasks(): void
    {
        RecurringTask::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->getJson('/api/recurring-tasks');

        $response->assertStatus(200);
        $response->assertJsonCount(3);
    }

    public function test_user_can_only_see_own_recurring_tasks(): void
    {
        $otherUser = User::factory()->create();

        RecurringTask::factory()->count(2)->create(['user_id' => $this->user->id]);
        RecurringTask::factory()->count(3)->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->getJson('/api/recurring-tasks');

        $response->assertStatus(200);
        $response->assertJsonCount(2);
    }

    public function test_user_can_create_daily_recurring_task(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/recurring-tasks', [
            'content' => 'Daily standup',
            'frequency_type' => 'daily',
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'content' => 'Daily standup',
            'frequency_type' => 'daily',
        ]);
        $this->assertDatabaseHas('recurring_tasks', [
            'user_id' => $this->user->id,
            'content' => 'Daily standup',
            'frequency_type' => 'daily',
        ]);
    }

    public function test_user_can_create_weekly_recurring_task(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/recurring-tasks', [
            'content' => 'Weekly report',
            'frequency_type' => 'weekly',
            'weekdays' => [1, 5], // Monday and Friday
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'content' => 'Weekly report',
            'frequency_type' => 'weekly',
        ]);
        $this->assertDatabaseHas('recurring_tasks', [
            'user_id' => $this->user->id,
            'content' => 'Weekly report',
            'frequency_type' => 'weekly',
        ]);
    }

    public function test_user_can_update_recurring_task(): void
    {
        $task = RecurringTask::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->putJson("/api/recurring-tasks/{$task->id}", [
            'content' => 'Updated content',
            'frequency_type' => 'daily',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['content' => 'Updated content']);
        $this->assertDatabaseHas('recurring_tasks', [
            'id' => $task->id,
            'content' => 'Updated content',
        ]);
    }

    public function test_user_cannot_update_other_users_recurring_task(): void
    {
        $otherUser = User::factory()->create();
        $task = RecurringTask::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->putJson("/api/recurring-tasks/{$task->id}", [
            'content' => 'Updated content',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_recurring_task(): void
    {
        $task = RecurringTask::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->deleteJson("/api/recurring-tasks/{$task->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('recurring_tasks', ['id' => $task->id]);
    }

    public function test_user_cannot_delete_other_users_recurring_task(): void
    {
        $otherUser = User::factory()->create();
        $task = RecurringTask::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->deleteJson("/api/recurring-tasks/{$task->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_toggle_recurring_task(): void
    {
        $task = RecurringTask::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/recurring-tasks/{$task->id}/toggle");

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $task->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('recurring_tasks', [
            'id' => $task->id,
            'is_active' => false,
        ]);
    }

    public function test_user_cannot_toggle_other_users_recurring_task(): void
    {
        $otherUser = User::factory()->create();
        $task = RecurringTask::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->postJson("/api/recurring-tasks/{$task->id}/toggle");

        $response->assertStatus(403);
    }

    public function test_create_recurring_task_validation(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/recurring-tasks', [
            'content' => '',
            'frequency_type' => 'invalid',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['content', 'frequency_type']);
    }

    public function test_weekdays_are_sorted(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/recurring-tasks', [
            'content' => 'Test task',
            'frequency_type' => 'weekly',
            'weekdays' => [5, 1, 3], // Unsorted
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('weekdays', [1, 3, 5]); // Should be sorted
    }
}
