<?php
// database/migrations/2026_04_01_000002_create_path_recommendations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('path_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_path_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quiz_id')->nullable()->constrained()->nullOnDelete();
            
            // Type de recommandation
            $table->string('type'); // weak_skill, next_level, exam_prep, daily_practice
            $table->integer('priority')->default(0); // 0 = basse, 1 = moyenne, 2 = haute
            
            // Contenu
            $table->string('title');
            $table->text('description');
            $table->json('metadata')->nullable(); // Données supplémentaires (skill_name, level, etc.)
            
            // Ciblage
            $table->string('target_skill')->nullable(); // reading, writing, listening, speaking, grammar, vocabulary
            $table->string('target_level')->nullable(); // A1, A2, B1, etc.
            
            // Statut
            $table->boolean('is_viewed')->default(false);
            $table->timestamp('viewed_at')->nullable();
            $table->boolean('is_applied')->default(false);
            $table->timestamp('applied_at')->nullable();
            $table->boolean('is_dismissed')->default(false);
            
            $table->timestamps();
            
            // Index
            $table->index(['learning_path_id', 'priority', 'is_viewed']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('path_recommendations');
    }
};