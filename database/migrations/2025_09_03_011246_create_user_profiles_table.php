<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('first_name', 100);
            $table->string('middle_name', 100);
            $table->string('last_name', 100);
            $table->string('contact_number', 100);
            $table->tinyInteger('status')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
