<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserVesselsTable extends Migration
{
    public function up()
    {
        Schema::create('user_vessels', function (Blueprint $table) {
            $table->id();
            $table->string('matricule')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('function')->nullable();
            $table->string('company')->nullable();
            $table->string('shift')->nullable();
            $table->string('workarea')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_vessels');
    }
}
