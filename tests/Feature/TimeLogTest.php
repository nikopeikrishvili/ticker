<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeLogTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_unauthenticated_user_cannot_access_time_logs(): void
    {
        $response = $this->getJson('/api/time-logs');

        $response->assertStatus(401);
    }

    public function test_user_can_list_time_logs_for_date(): void
    {
        $today = now()->toDateString();
        TimeLog::factory()->count(3)->forToday()->create(['user_id' => $this->user->id]);
        TimeLog::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'log_date' => now()->subDay()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/time-logs?date={$today}");

        $response->assertStatus(200);
        $response->assertJsonCount(3);
    }

    public function test_user_can_only_see_own_time_logs(): void
    {
        $otherUser = User::factory()->create();
        $today = now()->toDateString();

        TimeLog::factory()->count(2)->forToday()->create(['user_id' => $this->user->id]);
        TimeLog::factory()->count(3)->forToday()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->getJson("/api/time-logs?date={$today}");

        $response->assertStatus(200);
        $response->assertJsonCount(2);
    }

    public function test_user_can_create_time_log(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/time-logs', [
            'start_time' => '09:00',
            'end_time' => '10:30',
            'description' => 'Working on project',
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment(['description' => 'Working on project']);
        $this->assertDatabaseHas('time_logs', [
            'user_id' => $this->user->id,
            'description' => 'Working on project',
        ]);
    }

    public function test_user_can_create_time_log_with_category(): void
    {
        $category = Category::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->postJson('/api/time-logs', [
            'start_time' => '09:00',
            'end_time' => '10:30',
            'description' => 'Working on project',
            'category_id' => $category->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('time_logs', [
            'user_id' => $this->user->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_user_can_update_time_log(): void
    {
        $timeLog = TimeLog::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->putJson("/api/time-logs/{$timeLog->id}", [
            'description' => 'Updated description',
            'end_time' => '12:00',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('time_logs', [
            'id' => $timeLog->id,
            'description' => 'Updated description',
        ]);
    }

    public function test_user_cannot_update_other_users_time_log(): void
    {
        $otherUser = User::factory()->create();
        $timeLog = TimeLog::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->putJson("/api/time-logs/{$timeLog->id}", [
            'description' => 'Updated description',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_time_log(): void
    {
        $timeLog = TimeLog::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->deleteJson("/api/time-logs/{$timeLog->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('time_logs', ['id' => $timeLog->id]);
    }

    public function test_user_cannot_delete_other_users_time_log(): void
    {
        $otherUser = User::factory()->create();
        $timeLog = TimeLog::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->deleteJson("/api/time-logs/{$timeLog->id}");

        $response->assertStatus(403);
    }

    public function test_create_time_log_validation(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/time-logs', [
            'start_time' => 'invalid',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['start_time']);
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/time-logs', [
            'start_time' => '10:00',
            'end_time' => '09:00',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_time']);
    }
}
