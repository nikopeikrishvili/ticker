<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\RecurringTask;
use App\Models\Setting;
use App\Models\TimeLog;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $user1;
    private User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user1 = User::factory()->create();
        $this->user2 = User::factory()->create();
    }

    // ==================== TODOS ====================

    public function test_user_cannot_see_other_users_todos(): void
    {
        $today = now()->format('Y-m-d');
        $user1Todo = Todo::factory()->forDate($today)->create(['user_id' => $this->user1->id]);
        $user2Todo = Todo::factory()->forDate($today)->create(['user_id' => $this->user2->id]);

        $response = $this->actingAs($this->user1)
            ->getJson('/api/todos?date=' . $today);

        $response->assertOk();
        $todoIds = collect($response->json())->pluck('id')->toArray();

        $this->assertContains($user1Todo->id, $todoIds);
        $this->assertNotContains($user2Todo->id, $todoIds);
    }

    public function test_user_cannot_update_other_users_todo(): void
    {
        $otherUserTodo = Todo::factory()->create(['user_id' => $this->user2->id]);

        $response = $this->actingAs($this->user1)
            ->putJson("/api/todos/{$otherUserTodo->id}", [
                'content' => 'Hacked content',
            ]);

        // Should be 403 (forbidden) or 404 (not found due to user scoping)
        $this->assertTrue(in_array($response->status(), [403, 404]));

        $this->assertDatabaseHas('todos', [
            'id' => $otherUserTodo->id,
            'content' => $otherUserTodo->content,
        ]);
    }

    public function test_user_cannot_delete_other_users_todo(): void
    {
        $otherUserTodo = Todo::factory()->create(['user_id' => $this->user2->id]);

        $response = $this->actingAs($this->user1)
            ->deleteJson("/api/todos/{$otherUserTodo->id}");

        $this->assertTrue(in_array($response->status(), [403, 404]));
        $this->assertDatabaseHas('todos', ['id' => $otherUserTodo->id]);
    }

    public function test_user_cannot_start_working_on_other_users_todo(): void
    {
        $otherUserTodo = Todo::factory()->create(['user_id' => $this->user2->id]);

        $response = $this->actingAs($this->user1)
            ->postJson("/api/todos/{$otherUserTodo->id}/start");

        $this->assertTrue(in_array($response->status(), [403, 404]));
    }

    public function test_user_cannot_stop_working_on_other_users_todo(): void
    {
        $otherUserTodo = Todo::factory()->create(['user_id' => $this->user2->id]);

        $response = $this->actingAs($this->user1)
            ->postJson("/api/todos/{$otherUserTodo->id}/stop");

        $this->assertTrue(in_array($response->status(), [403, 404]));
    }

    // ==================== TIME LOGS ====================

    public function test_user_cannot_see_other_users_time_logs(): void
    {
        $today = now()->format('Y-m-d');
        $user1Log = TimeLog::factory()->create([
            'user_id' => $this->user1->id,
            'log_date' => $today,
        ]);
        $user2Log = TimeLog::factory()->create([
            'user_id' => $this->user2->id,
            'log_date' => $today,
        ]);

        $response = $this->actingAs($this->user1)
            ->getJson('/api/time-logs?date=' . $today);

        $response->assertOk();
        $logIds = collect($response->json())->pluck('id')->toArray();

        $this->assertContains($user1Log->id, $logIds);
        $this->assertNotContains($user2Log->id, $logIds);
    }

    public function test_user_cannot_update_other_users_time_log(): void
    {
        $otherUserLog = TimeLog::factory()->create(['user_id' => $this->user2->id]);

        $response = $this->actingAs($this->user1)
            ->putJson("/api/time-logs/{$otherUserLog->id}", [
                'description' => 'Hacked description',
            ]);

        $this->assertTrue(in_array($response->status(), [403, 404]));

        $this->assertDatabaseHas('time_logs', [
            'id' => $otherUserLog->id,
            'description' => $otherUserLog->description,
        ]);
    }

    public function test_user_cannot_delete_other_users_time_log(): void
    {
        $otherUserLog = TimeLog::factory()->create(['user_id' => $this->user2->id]);

        $response = $this->actingAs($this->user1)
            ->deleteJson("/api/time-logs/{$otherUserLog->id}");

        $this->assertTrue(in_array($response->status(), [403, 404]));
        $this->assertDatabaseHas('time_logs', ['id' => $otherUserLog->id]);
    }

    // ==================== CATEGORIES ====================

    public function test_user_cannot_see_other_users_categories(): void
    {
        $user1Category = Category::factory()->create(['user_id' => $this->user1->id]);
        $user2Category = Category::factory()->create(['user_id' => $this->user2->id]);

        $response = $this->actingAs($this->user1)
            ->getJson('/api/time-logs/categories');

        $response->assertOk();
        $categoryIds = collect($response->json())->pluck('id')->toArray();

        $this->assertContains($user1Category->id, $categoryIds);
        $this->assertNotContains($user2Category->id, $categoryIds);
    }

    public function test_user_cannot_update_other_users_category(): void
    {
        $otherUserCategory = Category::factory()->create(['user_id' => $this->user2->id]);

        $response = $this->actingAs($this->user1)
            ->putJson("/api/time-logs/categories/{$otherUserCategory->id}", [
                'name' => 'Hacked name',
                'color' => '#ff0000',
            ]);

        $this->assertTrue(in_array($response->status(), [403, 404]));

        $this->assertDatabaseHas('categories', [
            'id' => $otherUserCategory->id,
            'name' => $otherUserCategory->name,
        ]);
    }

    public function test_user_cannot_delete_other_users_category(): void
    {
        $otherUserCategory = Category::factory()->create(['user_id' => $this->user2->id]);

        $response = $this->actingAs($this->user1)
            ->deleteJson("/api/time-logs/categories/{$otherUserCategory->id}");

        $this->assertTrue(in_array($response->status(), [403, 404]));
        $this->assertDatabaseHas('categories', ['id' => $otherUserCategory->id]);
    }

    // ==================== SETTINGS ====================

    public function test_user_cannot_see_other_users_settings(): void
    {
        Setting::create([
            'user_id' => $this->user1->id,
            'key' => 'general.timezone',
            'value' => 'America/New_York',
            'category' => 'general',
        ]);

        Setting::create([
            'user_id' => $this->user2->id,
            'key' => 'general.timezone',
            'value' => 'Europe/London',
            'category' => 'general',
        ]);

        $response = $this->actingAs($this->user1)
            ->getJson('/api/settings');

        $response->assertOk();
        $settings = $response->json('settings');

        $this->assertEquals('America/New_York', $settings['general.timezone']);
        $this->assertNotEquals('Europe/London', $settings['general.timezone']);
    }

    public function test_user_settings_are_isolated(): void
    {
        // User 1 sets a setting
        $this->actingAs($this->user1)
            ->postJson('/api/settings', [
                'key' => 'general.timezone',
                'value' => 'America/New_York',
            ]);

        // User 2 sets the same setting to a different value
        $this->actingAs($this->user2)
            ->postJson('/api/settings', [
                'key' => 'general.timezone',
                'value' => 'Europe/London',
            ]);

        // Verify user 1 still sees their value
        $response1 = $this->actingAs($this->user1)
            ->getJson('/api/settings');
        $this->assertEquals('America/New_York', $response1->json('settings')['general.timezone']);

        // Verify user 2 sees their value
        $response2 = $this->actingAs($this->user2)
            ->getJson('/api/settings');
        $this->assertEquals('Europe/London', $response2->json('settings')['general.timezone']);
    }

    // ==================== RECURRING TASKS ====================

    public function test_user_cannot_see_other_users_recurring_tasks(): void
    {
        $user1Task = RecurringTask::factory()->create(['user_id' => $this->user1->id]);
        $user2Task = RecurringTask::factory()->create(['user_id' => $this->user2->id]);

        $response = $this->actingAs($this->user1)
            ->getJson('/api/recurring-tasks');

        $response->assertOk();
        $taskIds = collect($response->json())->pluck('id')->toArray();

        $this->assertContains($user1Task->id, $taskIds);
        $this->assertNotContains($user2Task->id, $taskIds);
    }

    public function test_user_cannot_update_other_users_recurring_task(): void
    {
        $otherUserTask = RecurringTask::factory()->create(['user_id' => $this->user2->id]);

        $response = $this->actingAs($this->user1)
            ->putJson("/api/recurring-tasks/{$otherUserTask->id}", [
                'content' => 'Hacked content',
                'frequency_type' => 'daily',
            ]);

        $this->assertTrue(in_array($response->status(), [403, 404]));

        $this->assertDatabaseHas('recurring_tasks', [
            'id' => $otherUserTask->id,
            'content' => $otherUserTask->content,
        ]);
    }

    public function test_user_cannot_delete_other_users_recurring_task(): void
    {
        $otherUserTask = RecurringTask::factory()->create(['user_id' => $this->user2->id]);

        $response = $this->actingAs($this->user1)
            ->deleteJson("/api/recurring-tasks/{$otherUserTask->id}");

        $this->assertTrue(in_array($response->status(), [403, 404]));
        $this->assertDatabaseHas('recurring_tasks', ['id' => $otherUserTask->id]);
    }

    public function test_user_cannot_toggle_other_users_recurring_task(): void
    {
        $otherUserTask = RecurringTask::factory()->create([
            'user_id' => $this->user2->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user1)
            ->postJson("/api/recurring-tasks/{$otherUserTask->id}/toggle");

        $this->assertTrue(in_array($response->status(), [403, 404]));

        $this->assertDatabaseHas('recurring_tasks', [
            'id' => $otherUserTask->id,
            'is_active' => true,
        ]);
    }

    // ==================== WEEKLY PLANNER ====================

    public function test_user_cannot_assign_other_users_todo_to_day(): void
    {
        $otherUserTodo = Todo::factory()->create(['user_id' => $this->user2->id]);

        $response = $this->actingAs($this->user1)
            ->postJson('/api/weekly/assign', [
                'todo_id' => $otherUserTodo->id,
                'week_key' => now()->format('Y-\\WW'),
                'day_of_week' => 1,
            ]);

        $this->assertTrue(in_array($response->status(), [403, 404]));
    }

    public function test_user_cannot_move_other_users_todo_to_backlog(): void
    {
        $otherUserTodo = Todo::factory()->create(['user_id' => $this->user2->id]);

        $response = $this->actingAs($this->user1)
            ->postJson('/api/weekly/backlog', [
                'todo_id' => $otherUserTodo->id,
            ]);

        $this->assertTrue(in_array($response->status(), [403, 404]));
    }
}
