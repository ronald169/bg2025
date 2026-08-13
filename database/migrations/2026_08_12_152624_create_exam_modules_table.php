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
        Schema::create('exam_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')
                  ->nullable()
                  ->constrained('exams')
                  ->nullOnDelete();
            $table->string('name'); // Lesen, Hören, Schreiben, Sprechen
            $table->string('code', 20); // lesen, horen, schreiben, sprechen
            $table->integer('order')->default(0);
            $table->integer('duration_minutes');
            $table->text('general_instructions')->nullable();
            $table->boolean('has_global_numbering')->default(true);
            $table->softDeletes(); // Préserve l'historique des tentatives
            $table->timestamps();

            // Unique par examen, pas globalement
            $table->unique(['exam_id', 'code']);
            $table->index(['exam_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_modules');
    }
};
