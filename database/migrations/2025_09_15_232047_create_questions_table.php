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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->string('type')->default('true_false'); // multiple_choice | true_false | short_answer
            $table->json('options')->nullable(); // pour les question a choix multiple
            $table->string('correct_answer');
            $table->integer('points')->default(1);
            $table->text('explanation')->nullable();

            $table->foreignId('quiz_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
