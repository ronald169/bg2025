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
        Schema::create('exam_module_teil', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('exam_modules')->cascadeOnDelete();
            $table->foreignId('teil_id')->constrained('exam_teils')->cascadeOnDelete();
            $table->integer('order')->default(0); // Ordre du teil dans CE module
            $table->timestamps();

            $table->unique(['module_id', 'teil_id']);
            $table->index(['module_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_module_teil');
    }
};
