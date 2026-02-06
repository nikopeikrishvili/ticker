<?php

namespace Tests\Feature;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeeklyPlannerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_unauthenticated_user_cannot_access_weekly_planner(): void
    {
        $response = $this->getJson('/api/weekly');

        $response->assertStatus(401);
    }

    public function test_user_can_get_weekly_data(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/weekly');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'weekKey',
            'weekDisplay',
            'prevWeekKey',
            'nextWeekKey',
            'isCurrentWeek',
            'days',
            'backlog',
        ]);
    }

    public function test_user_can_get_specific_week_data(): void
    {
        $weekKey = '2026-W06';

        $response = $this->actingAs($this->user)->getJson("/api/weekly?week={$weekKey}");

        $response->assertStatus(200);
        $response->assertJsonPath('weekKey', $weekKey);
    }

    public function test_weekly_data_includes_todos_for_each_day(): void
    {
        $weekKey = now()->format('Y-\\WW');
        $monday = now()->startOfWeek()->toDateString();

        Todo::factory()->forDate($monday)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->getJson("/api/weekly?week={$weekKey}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'days.1.current');
    }

    public function test_weekly_data_includes_backlog(): void
    {
        Todo::factory()->backlog()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->getJson('/api/weekly');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'backlog');
    }

    public function test_user_can_assign_todo_to_day(): void
    {
        $todo = Todo::factory()->backlog()->create(['user_id' => $this->user->id]);
        $weekKey = now()->format('Y-\\WW');

        $response = $this->actingAs($this->user)->postJson('/api/weekly/assign', [
            'todo_id' => $todo->id,
            'week_key' => $weekKey,
            'day_of_week' => 1, // Monday
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $todo->refresh();
        $this->assertNotNull($todo->todo_date);
    }

    public function test_user_can_move_todo_to_backlog(): void
    {
        $todo = Todo::factory()->forToday()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->postJson('/api/weekly/backlog', [
            'todo_id' => $todo->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $todo->refresh();
        $this->assertNull($todo->todo_date);
    }

    public function test_user_can_carry_over_incomplete_tasks_to_next_week(): void
    {
        $weekKey = now()->format('Y-\\WW');
        $monday = now()->startOfWeek()->toDateString();

        $incompleteTodo = Todo::factory()->todo()->forDate($monday)->create(['user_id' => $this->user->id]);
        $completedTodo = Todo::factory()->completed()->forDate($monday)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->postJson('/api/weekly/carry-over', [
            'week_key' => $weekKey,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'movedCount' => 1,
        ]);

        $incompleteTodo->refresh();
        $this->assertEquals(
            now()->startOfWeek()->addWeek()->toDateString(),
            $incompleteTodo->todo_date->toDateString()
        );

        $completedTodo->refresh();
        $this->assertEquals($monday, $completedTodo->todo_date->toDateString());
    }

    public function test_user_can_only_see_own_data_in_weekly_planner(): void
    {
        $otherUser = User::factory()->create();
        $monday = now()->startOfWeek()->toDateString();

        Todo::factory()->forDate($monday)->count(2)->create(['user_id' => $this->user->id]);
        Todo::factory()->forDate($monday)->count(3)->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->getJson('/api/weekly');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'days.1.current');
    }

    public function test_weekly_page_is_accessible(): void
    {
        $response = $this->actingAs($this->user)->get('/weekly');

        $response->assertStatus(200);
    }

    public function test_assign_to_day_validation(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/weekly/assign', [
            'todo_id' => 999,
            'week_key' => '',
            'day_of_week' => 10,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['todo_id', 'week_key', 'day_of_week']);
    }

    public function test_move_to_backlog_validation(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/weekly/backlog', [
            'todo_id' => 999,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['todo_id']);
    }
}
