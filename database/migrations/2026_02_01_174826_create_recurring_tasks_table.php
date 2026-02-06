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
        Schema::create('recurring_tasks', function (Blueprint $table) {
            $table->id();
            $table->text('content');
            $table->enum('frequency_type', ['daily', 'weekly'])->default('daily');
            $table->json('weekdays')->nullable(); // [1,2,3,4,5] for Mon-Fri (1=Mon, 7=Sun)
            $table->boolean('is_active')->default(true);
            $table->date('last_generated_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_tasks');
    }
};
