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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');

            // Statut
            $table->enum('status', ['active', 'completed', 'dropped'])->default('active');

            // Dates
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Métriques
            $table->float('progress')->default(0); // pourcentage
            $table->integer('last_lesson_id')->nullable();

            // Paiement (optionnel)
            $table->string('payment_id')->nullable();
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->string('payment_status')->nullable();

            $table->timestamps();

            // Index pour optimiser
            $table->unique(['user_id', 'course_id']);
            $table->index(['user_id', 'status']);
            $table->index(['course_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
