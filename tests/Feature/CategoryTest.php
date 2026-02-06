<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_unauthenticated_user_cannot_access_categories(): void
    {
        $response = $this->getJson('/api/time-logs/categories');

        $response->assertStatus(401);
    }

    public function test_user_can_list_categories(): void
    {
        Category::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->getJson('/api/time-logs/categories');

        $response->assertStatus(200);
        $response->assertJsonCount(3);
    }

    public function test_user_can_only_see_own_categories(): void
    {
        $otherUser = User::factory()->create();

        Category::factory()->count(2)->create(['user_id' => $this->user->id]);
        Category::factory()->count(3)->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->getJson('/api/time-logs/categories');

        $response->assertStatus(200);
        $response->assertJsonCount(2);
    }

    public function test_user_can_create_category(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/time-logs/categories', [
            'name' => 'Development',
            'icon' => 'code',
            'color' => '#3498db',
            'keywords' => 'coding, programming',
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment(['name' => 'Development']);
        $this->assertDatabaseHas('categories', [
            'user_id' => $this->user->id,
            'name' => 'Development',
        ]);
    }

    public function test_user_can_update_category(): void
    {
        $category = Category::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->putJson("/api/time-logs/categories/{$category->id}", [
            'name' => 'Updated Name',
            'color' => '#e74c3c',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Updated Name']);
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Name',
            'color' => '#e74c3c',
        ]);
    }

    public function test_user_cannot_update_other_users_category(): void
    {
        $otherUser = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->putJson("/api/time-logs/categories/{$category->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_category(): void
    {
        $category = Category::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->deleteJson("/api/time-logs/categories/{$category->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_user_cannot_delete_other_users_category(): void
    {
        $otherUser = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->deleteJson("/api/time-logs/categories/{$category->id}");

        $response->assertStatus(403);
    }

    public function test_create_category_validation(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/time-logs/categories', [
            'name' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }
}
