<?php

namespace Tests\Feature;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_unauthenticated_user_cannot_access_todos(): void
    {
        $response = $this->getJson('/api/todos');

        $response->assertStatus(401);
    }

    public function test_user_can_list_todos_for_today(): void
    {
        $today = now()->toDateString();
        Todo::factory()->count(3)->forDate($today)->create(['user_id' => $this->user->id]);
        Todo::factory()->count(2)->forDate(now()->subDay()->toDateString())->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->getJson("/api/todos?date={$today}");

        $response->assertStatus(200);
        $response->assertJsonCount(3);
    }

    public function test_user_can_only_see_own_todos(): void
    {
        $otherUser = User::factory()->create();
        $today = now()->toDateString();

        Todo::factory()->count(2)->forDate($today)->create(['user_id' => $this->user->id]);
        Todo::factory()->count(3)->forDate($today)->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->getJson("/api/todos?date={$today}");

        $response->assertStatus(200);
        $response->assertJsonCount(2);
    }

    public function test_user_can_create_todo(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/todos', [
            'content' => 'Test todo',
            'status' => 'todo',
            'priority' => 3,
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment(['content' => 'Test todo']);
        $this->assertDatabaseHas('todos', [
            'user_id' => $this->user->id,
            'content' => 'Test todo',
        ]);
    }

    public function test_user_can_create_todo_with_date(): void
    {
        $date = '2026-02-10';

        $response = $this->actingAs($this->user)->postJson('/api/todos', [
            'content' => 'Test todo with date',
            'todo_date' => $date,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('todos', [
            'content' => 'Test todo with date',
            'todo_date' => $date,
        ]);
    }

    public function test_user_can_update_todo(): void
    {
        $todo = Todo::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->putJson("/api/todos/{$todo->id}", [
            'content' => 'Updated content',
            'status' => 'in_progress',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['content' => 'Updated content']);
        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'content' => 'Updated content',
            'status' => 'in_progress',
        ]);
    }

    public function test_user_cannot_update_other_users_todo(): void
    {
        $otherUser = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->putJson("/api/todos/{$todo->id}", [
            'content' => 'Updated content',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_todo(): void
    {
        $todo = Todo::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->deleteJson("/api/todos/{$todo->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('todos', ['id' => $todo->id]);
    }

    public function test_user_cannot_delete_other_users_todo(): void
    {
        $otherUser = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->deleteJson("/api/todos/{$todo->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('todos', ['id' => $todo->id]);
    }

    public function test_user_can_mark_todo_as_completed(): void
    {
        $todo = Todo::factory()->todo()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->putJson("/api/todos/{$todo->id}", [
            'is_completed' => true,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'is_completed' => true,
            'status' => 'done',
        ]);
    }

    public function test_user_can_start_working_on_todo(): void
    {
        $todo = Todo::factory()->todo()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->postJson("/api/todos/{$todo->id}/start");

        $response->assertStatus(200);
        $response->assertJsonStructure(['todo', 'time_log']);
        $this->assertDatabaseHas('time_logs', [
            'todo_id' => $todo->id,
            'user_id' => $this->user->id,
            'end_time' => null,
        ]);
    }

    public function test_user_can_stop_working_on_todo(): void
    {
        $todo = Todo::factory()->todo()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user)->postJson("/api/todos/{$todo->id}/start");

        $response = $this->actingAs($this->user)->postJson("/api/todos/{$todo->id}/stop");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('time_logs', [
            'todo_id' => $todo->id,
            'end_time' => null,
        ]);
    }

    public function test_user_can_get_statuses(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/todos/statuses');

        $response->assertStatus(200);
        $response->assertJsonStructure(['backlog', 'todo', 'in_progress', 'done']);
    }

    public function test_user_can_get_pending_from_previous_dates(): void
    {
        $yesterday = now()->subDay()->toDateString();
        $today = now()->toDateString();

        Todo::factory()->todo()->forDate($yesterday)->create(['user_id' => $this->user->id]);
        Todo::factory()->completed()->forDate($yesterday)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->getJson("/api/todos/pending-previous?date={$today}");

        $response->assertStatus(200);
        $response->assertJsonCount(1);
    }

    public function test_user_can_carry_over_todos(): void
    {
        $yesterday = now()->subDay()->toDateString();
        $today = now()->toDateString();

        $todo1 = Todo::factory()->todo()->forDate($yesterday)->create(['user_id' => $this->user->id]);
        $todo2 = Todo::factory()->todo()->forDate($yesterday)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->postJson('/api/todos/carry-over', [
            'task_ids' => [$todo1->id, $todo2->id],
            'target_date' => $today,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'moved_count' => 2]);
        $this->assertDatabaseHas('todos', ['id' => $todo1->id, 'todo_date' => $today]);
        $this->assertDatabaseHas('todos', ['id' => $todo2->id, 'todo_date' => $today]);
    }

    public function test_create_todo_validation(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/todos', [
            'content' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['content']);
    }
}
