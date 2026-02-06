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
        Schema::table('time_logs', function (Blueprint $table) {
            $table->date('log_date')->default(now()->toDateString())->after('id');
            $table->index('log_date');
        });

        // Set existing records to use their created_at date
        DB::table('time_logs')->update([
            'log_date' => DB::raw("DATE(created_at)"),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_logs', function (Blueprint $table) {
            $table->dropIndex(['log_date']);
            $table->dropColumn('log_date');
        });
    }
};
