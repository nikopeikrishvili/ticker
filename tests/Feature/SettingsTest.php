<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_unauthenticated_user_cannot_access_settings(): void
    {
        $response = $this->getJson('/api/settings');

        $response->assertStatus(401);
    }

    public function test_user_can_get_all_settings(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/settings');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'settings',
            'defaults',
            'server_date',
            'server_time',
        ]);
    }

    public function test_settings_include_defaults(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/settings');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals('Asia/Tbilisi', $data['settings']['general.timezone']);
    }

    public function test_user_can_get_settings_by_category(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/settings/category/appearance');

        $response->assertStatus(200);
        $response->assertJsonStructure(['settings']);
    }

    public function test_user_can_update_setting(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/settings', [
            'key' => 'general.timezone',
            'value' => 'Europe/London',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('settings', [
            'user_id' => $this->user->id,
            'key' => 'general.timezone',
            'value' => 'Europe/London',
        ]);
    }

    public function test_user_can_update_settings_batch(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/settings/batch', [
            'settings' => [
                ['key' => 'appearance.primary_color', 'value' => '#ff0000'],
                ['key' => 'appearance.background_color', 'value' => '#ffffff'],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('settings', [
            'user_id' => $this->user->id,
            'key' => 'appearance.primary_color',
            'value' => '#ff0000',
        ]);
        $this->assertDatabaseHas('settings', [
            'user_id' => $this->user->id,
            'key' => 'appearance.background_color',
            'value' => '#ffffff',
        ]);
    }

    public function test_user_can_reset_single_setting(): void
    {
        Setting::factory()->create([
            'user_id' => $this->user->id,
            'key' => 'general.timezone',
            'value' => 'Europe/London',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/settings/reset', [
            'key' => 'general.timezone',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'key' => 'general.timezone',
            'value' => 'Asia/Tbilisi',
        ]);
        $this->assertDatabaseMissing('settings', [
            'user_id' => $this->user->id,
            'key' => 'general.timezone',
        ]);
    }

    public function test_user_can_reset_all_settings(): void
    {
        Setting::factory()->create([
            'user_id' => $this->user->id,
            'key' => 'general.timezone',
            'value' => 'Europe/London',
        ]);
        Setting::factory()->create([
            'user_id' => $this->user->id,
            'key' => 'appearance.primary_color',
            'value' => '#ff0000',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/settings/reset-all');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('settings', ['user_id' => $this->user->id]);
    }

    public function test_users_have_separate_settings(): void
    {
        $otherUser = User::factory()->create();

        // Set different timezone for each user
        $response1 = $this->actingAs($this->user)->postJson('/api/settings', [
            'key' => 'general.timezone',
            'value' => 'Europe/London',
        ]);
        $response1->assertJson(['success' => true]);

        $response2 = $this->actingAs($otherUser)->postJson('/api/settings', [
            'key' => 'general.timezone',
            'value' => 'America/New_York',
        ]);
        $response2->assertJson(['success' => true]);

        // Verify database has correct values
        $this->assertDatabaseHas('settings', [
            'user_id' => $this->user->id,
            'key' => 'general.timezone',
            'value' => 'Europe/London',
        ]);
        $this->assertDatabaseHas('settings', [
            'user_id' => $otherUser->id,
            'key' => 'general.timezone',
            'value' => 'America/New_York',
        ]);

        // Check each user sees their own settings via API
        $getResponse1 = $this->actingAs($this->user)->getJson('/api/settings');
        $data1 = $getResponse1->json();
        $this->assertEquals('Europe/London', $data1['settings']['general.timezone']);

        $getResponse2 = $this->actingAs($otherUser)->getJson('/api/settings');
        $data2 = $getResponse2->json();
        $this->assertEquals('America/New_York', $data2['settings']['general.timezone']);
    }

    public function test_update_setting_validation(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/settings', [
            'key' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['key', 'value']);
    }
}
