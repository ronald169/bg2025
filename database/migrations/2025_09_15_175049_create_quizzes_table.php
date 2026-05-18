<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('lesson_id')->constrained()->onDelete('cascade');

            // Informations
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('time_limit')->nullable(); // en minutes
            $table->integer('passing_score')->default(70); // pourcentage
            $table->integer('max_attempts')->default(1);
            $table->boolean('is_published')->default(false);
            $table->integer('order')->default(0);

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('robots')->default('index,follow');

            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index(['lesson_id', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
