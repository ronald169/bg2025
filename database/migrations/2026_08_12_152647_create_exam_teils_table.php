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
        Schema::create('exam_teils', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // ex: "Teil 1"
            $table->integer('order')->default(0);
            $table->integer('duration_minutes')->nullable();
            $table->text('instructions')->nullable();
            $table->longText('content')->nullable(); // Texte / transcription
            $table->string('content_image')->nullable(); // MVP : 1 image. Pour multi-images → Spatie Media Library plus tard
            $table->string('audio_path')->nullable(); // MVP : 1 fichier audio
            $table->string('source')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_teils');
    }
};
