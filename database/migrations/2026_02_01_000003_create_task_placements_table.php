<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('todo_id')->constrained()->onDelete('cascade');
            $table->string('week_key', 10); // Format: '2026-W05'
            $table->tinyInteger('day_of_week'); // 1=Mon, 2=Tue, 3=Wed, 4=Thu, 5=Fri
            $table->boolean('is_current')->default(true); // false = ghost/annotation
            $table->foreignId('moved_to_id')->nullable()->constrained('task_placements')->onDelete('set null');
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['week_key', 'day_of_week']);
            $table->index(['todo_id', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_placements');
    }
};
