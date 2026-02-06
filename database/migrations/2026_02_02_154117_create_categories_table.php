<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Seed default categories
        $categories = [
            ['name' => 'Call', 'icon' => 'phone', 'color' => '#22c55e', 'order' => 1],
            ['name' => 'Slack', 'icon' => 'message-square', 'color' => '#8b5cf6', 'order' => 2],
            ['name' => 'Coding', 'icon' => 'code', 'color' => '#3b82f6', 'order' => 3],
            ['name' => 'Code Review', 'icon' => 'git-pull-request', 'color' => '#f97316', 'order' => 4],
            ['name' => 'Research', 'icon' => 'search', 'color' => '#eab308', 'order' => 5],
            ['name' => 'Task Generation', 'icon' => 'list-todo', 'color' => '#ec4899', 'order' => 6],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->insert([
                'name' => $category['name'],
                'icon' => $category['icon'],
                'color' => $category['color'],
                'order' => $category['order'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
