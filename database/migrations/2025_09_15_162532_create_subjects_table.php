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
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // mathematique, physque...
            $table->text('description')->nullable();
            $table->string('level')->nullable(); // college | lycee | 6,5,4,3,2,1,Tle
            $table->string('color')->default('3B82F6');
            $table->string('icon')->nullable('book');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
