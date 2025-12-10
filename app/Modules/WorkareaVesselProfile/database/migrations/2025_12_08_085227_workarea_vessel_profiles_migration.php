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
        Schema::create('workarea_vessel_profiles', function (Blueprint $table) {
            $table->id();
        
            $table->string('workarea');            // Example: Quai, Lashing, Hatch
            $table->string('vessel_name');         // Example: MSC Tangier
            $table->string('device')->nullable();  // Example: Turnstile P4-2 IN
        
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
