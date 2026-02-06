<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->date('todo_date')->nullable()->after('id');
            $table->index('todo_date');
        });

        // Set existing records to use their created_at date
        DB::table('todos')->whereNull('todo_date')->update([
            'todo_date' => DB::raw("DATE(created_at)"),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropIndex(['todo_date']);
            $table->dropColumn('todo_date');
        });
    }
};
