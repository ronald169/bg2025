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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();

            // Rôle et statut
            $table->string('role')->default('student'); // 'student', 'teacher', 'admin'
            $table->string('status')->default('active'); //'active', 'inactive', 'suspended'

            // Migrations à ajouter Specialement AllemandExpress
            $table->string('german_level')->nullable(); // A1, A2, B1, B2, C1, C2
            $table->string('learning_goal')->nullable(); // certification, conversation, travel, business
            $table->text('motivation')->nullable();
            $table->boolean('study_reminders')->default(true);

            // Informations personnelles
            $table->string('phone')->nullable();
            $table->string('bio')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('level')->nullable(); // 'college', 'lycee', 'terminale'
            $table->string('profile_photo_path')->nullable();

            // Préférences
            $table->boolean('email_notifications')->default(true);
            $table->boolean('sms_notifications')->default(false);
            $table->boolean('push_notifications')->default(true);
            $table->string('language')->default('fr');
            $table->string('timezone')->default('UTC');

            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index('email');
            $table->index('role');
            $table->index('status');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
