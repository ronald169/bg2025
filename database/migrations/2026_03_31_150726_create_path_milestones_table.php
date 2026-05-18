<?php
// database/migrations/2026_04_01_000001_create_path_milestones_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('path_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_path_id')->constrained()->onDelete('cascade');
            
            // Type et identification
            $table->string('type'); // first_lesson, first_quiz, level_up_a2, streak_7, etc.
            $table->string('title');
            $table->text('description')->nullable();
            
            // Conditions d'obtention
            $table->integer('required_points')->default(0);
            $table->integer('required_hours')->default(0);
            $table->integer('required_skill_level')->default(0);
            $table->string('required_level')->nullable(); // A1, A2, B1, etc.
            
            // Récompenses
            $table->integer('reward_points')->default(0);
            $table->string('reward_badge')->nullable();
            $table->string('reward_certificate')->nullable();
            
            // Statut
            $table->timestamp('achieved_at')->nullable();
            $table->boolean('is_achieved')->default(false);
            
            $table->timestamps();
            
            // Index pour les recherches
            $table->index(['learning_path_id', 'is_achieved']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('path_milestones');
    }
};