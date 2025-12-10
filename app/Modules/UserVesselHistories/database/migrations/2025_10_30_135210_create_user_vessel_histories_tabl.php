<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_vessel_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_vessel_id')->constrained('user_vessels')->onDelete('cascade');
            $table->string('shift');          // Shift name/code
            $table->date('work_date');        // Date of the shift
            $table->integer('overtime');        // Date of the shift
            $table->string('workarea')->nullable(); // Optional work area
            $table->timestamps();

            $table->foreignId('profile_id')
                ->nullable()
                ->constrained('workarea_vessel_profiles')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_vessel_histories');
    }
};
