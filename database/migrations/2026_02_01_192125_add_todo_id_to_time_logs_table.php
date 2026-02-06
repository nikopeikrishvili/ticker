<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('time_logs', function (Blueprint $table) {
            $table->foreignId('todo_id')->nullable()->after('id')->constrained('todos')->nullOnDelete();
            $table->index('todo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_logs', function (Blueprint $table) {
            $table->dropForeign(['todo_id']);
            $table->dropIndex(['todo_id']);
            $table->dropColumn('todo_id');
        });
    }
};
