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
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            // Relations
            $table->foreignId('course_id')->constrained()->onDelete('cascade');

            // Informations de base
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->longText('content')->nullable();

            // Média
            $table->string('video_url')->nullable();
            $table->string('video_type')->default('youtube'); // youtube, vimeo, local
            $table->string('video_id')->nullable(); // ID du vidéo (YouTube, Vimeo)
            $table->integer('duration')->default(0); // en secondes

            // Ressources
            $table->json('attachments')->nullable();
            $table->json('resources')->nullable(); // liens, PDF, etc.

            // Organisation
            $table->integer('order')->default(0);
            $table->boolean('is_free')->default(false);
            $table->boolean('is_published')->default(true);
            $table->timestamp('scheduled_at')->nullable();

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('robots')->default('index,follow');
            $table->string('twitter_title')->nullable();
            $table->text('twitter_description')->nullable();
            $table->string('twitter_image')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('robots')->default('index,follow');

            // Quiz associé (optionnel)
            // $table->foreignId('quiz_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedBigInteger('quiz_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Index pour optimiser
            $table->unique(['course_id', 'slug']);
            $table->index(['course_id', 'order']);
            $table->index('is_published');
            $table->index('is_free');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
