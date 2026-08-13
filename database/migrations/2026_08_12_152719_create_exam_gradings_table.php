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
        Schema::create('exam_gradings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('exam_questions')->cascadeOnDelete();

            // Contient tout : label QCM ("a"), texte court ("d"), texte long (Schreiben), "Richtig", etc.
            $table->longText('text_answer')->nullable();

            // Autocorrection pour QCM / true_false / yes_no / short_answer
            $table->boolean('is_correct')->nullable();
            $table->decimal('points_earned', 4, 1)->default(0);

            $table->timestamps();

            $table->unique(['exam_attempt_id', 'question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_gradings');
    }
};
