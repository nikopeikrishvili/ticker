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
        Schema::table('categories', function (Blueprint $table) {
            $table->text('keywords')->nullable()->after('color');
        });

        // Add default keywords for existing categories
        $defaults = [
            'Call' => 'call,ზარი,phone',
            'Slack' => 'slack,chat,message',
            'Coding' => 'code,coding,develop,implementation',
            'Code Review' => 'review,pr,pull request,merge',
            'Research' => 'research,investigate,analyze',
            'Task Generation' => 'task,planning,spec',
        ];

        foreach ($defaults as $name => $keywords) {
            DB::table('categories')
                ->where('name', $name)
                ->update(['keywords' => $keywords]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('keywords');
        });
    }
};
