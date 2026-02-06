<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->string('current_week_key', 10)->nullable()->after('order'); // null = backlog
            $table->tinyInteger('current_day')->nullable()->after('current_week_key'); // 1-5 for Mon-Fri

            $table->index('current_week_key');
        });
    }

    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropIndex(['current_week_key']);
            $table->dropColumn(['current_week_key', 'current_day']);
        });
    }
};
