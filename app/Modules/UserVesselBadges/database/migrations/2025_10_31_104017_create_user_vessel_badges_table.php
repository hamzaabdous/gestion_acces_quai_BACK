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
        Schema::create('user_vessel_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_vessel_history_id')
                  ->constrained('user_vessel_histories')
                  ->onDelete('cascade');
            $table->string('badge_place'); // e.g., Gate A, Dock 1
            $table->timestamp('badge_date'); // date & time of badge
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_vessel_badges');
    }
};
