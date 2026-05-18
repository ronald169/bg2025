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
        Schema::create('progress', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('lesson_id')->constrained()->onDelete('cascade');

            // Progression
            $table->boolean('is_completed')->default(false);
            $table->integer('time_spent')->default(0); // en secondes
            $table->integer('watch_percentage')->default(0); // pourcentage visionné

            // Notes utilisateur
            $table->text('notes')->nullable();

            // Métadonnées
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_accessed')->nullable();

            $table->timestamps();

            // Index pour optimiser
            $table->unique(['user_id', 'lesson_id']);
            $table->index(['user_id', 'is_completed']);
            $table->index('last_accessed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progress');
    }
};
