<?php
// database/migrations/2026_03_31_000001_create_learning_paths_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_paths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Niveaux
            $table->string('current_level')->default('A1'); // A1, A2, B1, B2, C1, C2
            $table->string('target_level')->default('B1');
            $table->string('learning_goal'); // certification, conversation, travel, business
            
            // Progression globale
            $table->integer('overall_progress')->default(0); // 0-100%
            $table->integer('total_points')->default(0);
            $table->integer('total_hours_studied')->default(0);
            $table->integer('total_quizzes_taken')->default(0);
            $table->integer('total_quizzes_passed')->default(0);
            
            // Certification
            $table->string('target_certification')->nullable(); // Goethe, ÖSD, TELC
            $table->date('target_exam_date')->nullable();
            $table->boolean('exam_registered')->default(false);
            
            // Métriques de compétence (0-100)
            $table->integer('reading_skill')->default(0);
            $table->integer('writing_skill')->default(0);
            $table->integer('listening_skill')->default(0);
            $table->integer('speaking_skill')->default(0);
            $table->integer('grammar_skill')->default(0);
            $table->integer('vocabulary_skill')->default(0);
            
            // Objectifs personnalisés
            $table->json('custom_goals')->nullable(); // Objectifs spécifiques de l'utilisateur
            $table->json('milestones')->nullable(); // Jalons atteints
            
            // Dates importantes
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('estimated_completion_date')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Index pour optimiser les requêtes
            $table->index(['user_id', 'is_active']);
            $table->index('current_level');
            $table->index('target_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_paths');
    }
};