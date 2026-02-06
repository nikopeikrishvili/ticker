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
        Schema::table('todos', function (Blueprint $table) {
            $table->string('task_id')->nullable()->after('id');
            $table->string('jira_key')->nullable()->after('task_id');
            $table->string('jira_url')->nullable()->after('jira_key');
            $table->index('task_id');
            $table->index('jira_key');
        });

        // Generate task IDs for existing todos
        $todos = DB::table('todos')->whereNull('task_id')->orderBy('id')->get();
        foreach ($todos as $todo) {
            DB::table('todos')
                ->where('id', $todo->id)
                ->update(['task_id' => 'TASK-' . $todo->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropIndex(['task_id']);
            $table->dropIndex(['jira_key']);
            $table->dropColumn(['task_id', 'jira_key', 'jira_url']);
        });
    }
};
