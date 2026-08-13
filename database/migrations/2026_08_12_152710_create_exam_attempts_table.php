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
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('exam_id')->nullable()->constrained('exams')->nullOnDelete();
            $table->foreignId('module_id')->nullable()->constrained('exam_modules')->nullOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('last_activity_at')->nullable(); // Pour reprise après inactivité
            $table->string('status', 20)->default('in_progress'); // in_progress, completed, abandoned, grading
            $table->decimal('total_score', 6, 2)->nullable();
            $table->integer('max_possible_score')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['exam_id', 'module_id']);
            $table->index('last_activity_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
