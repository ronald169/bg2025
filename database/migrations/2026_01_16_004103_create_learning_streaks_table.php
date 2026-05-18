<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_streaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('current_streak')->default(0);
            $table->integer('longest_streak')->default(0);
            $table->dateTime('last_study_date')->nullable();
            $table->integer('total_study_days')->default(0);
            $table->timestamps();

            $table->unique('user_id');
            $table->index('current_streak');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_streaks');
    }
};
