<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quiz_id')->constrained()->onDelete('cascade');

            $table->text('question');
            $table->string('type')->default('multiple_choice'); // multiple_choice, true_false, text
            $table->json('options')->nullable(); // pour les choix multiples
            $table->json('correct_answer');
            $table->integer('points')->default(1);
            $table->text('explanation')->nullable();
            $table->integer('order')->default(0);

            $table->timestamps();

            $table->index(['quiz_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};
