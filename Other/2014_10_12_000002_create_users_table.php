<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->morphs('userable');
            $table->string('name');
            $table->string('username')->unique();
            $table->string('grade');
            $table->string('password');
            $table->boolean('isBlocked')->default(false);
            $table->boolean('hasImage')->default(false);
            $table->string('profile_image')->default("blank.png");
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }

}


