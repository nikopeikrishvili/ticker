<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create default user if none exists
        $user = User::first();

        if (!$user) {
            $user = User::create([
                'name' => 'Default User',
                'email' => 'user@ticker.local',
                'password' => Hash::make('password'),
            ]);
        }

        $userId = $user->id;

        // Assign all orphaned data to the default user
        DB::table('todos')->whereNull('user_id')->update(['user_id' => $userId]);
        DB::table('time_logs')->whereNull('user_id')->update(['user_id' => $userId]);
        DB::table('categories')->whereNull('user_id')->update(['user_id' => $userId]);
        DB::table('settings')->whereNull('user_id')->update(['user_id' => $userId]);
        DB::table('recurring_tasks')->whereNull('user_id')->update(['user_id' => $userId]);
        DB::table('task_placements')->whereNull('user_id')->update(['user_id' => $userId]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot safely reverse this migration
    }
};
