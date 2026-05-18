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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            
            // Informations de base
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->text('short_description')->nullable();

            // Relations
            $table->foreignId('subject_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('teacher_id')->nullable()->constrained('users')->onDelete('set null');

            // Niveau et difficulté
            $table->string('level', 50)->default('A1');
            $table->string('difficulty', 50)->default('beginner');

            // Durée estimée (en minutes)
            $table->integer('estimated_duration')->default(0);

            // Média
            $table->string('thumbnail')->nullable();
            $table->string('preview_video')->nullable();
            $table->string('video_type')->default('youtube'); // youtube, vimeo, local

            // Contenu additionnel
            $table->json('requirements')->nullable();
            $table->json('what_you_will_learn')->nullable();
            $table->json('tags')->nullable();

            // Statut et visibilité
            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_free')->default(false);

            // Prix
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('sale_price', 10, 2)->nullable();

            // Métriques
            $table->integer('views_count')->default(0);
            $table->integer('enrollments_count')->default(0);
            $table->float('average_rating')->default(0);
            $table->integer('reviews_count')->default(0);

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable();
            $table->string('twitter_title')->nullable();
            $table->text('twitter_description')->nullable();
            $table->string('twitter_image')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('robots')->default('index,follow');

            $table->timestamps();
            $table->softDeletes();

            // Index pour optimiser les recherches
            $table->index('slug');
            $table->index('level');
            $table->index('difficulty');
            $table->index('is_published');
            $table->index('is_featured');
            $table->index('price');
            $table->index(['subject_id', 'is_published']);
            $table->index(['level', 'is_published']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
