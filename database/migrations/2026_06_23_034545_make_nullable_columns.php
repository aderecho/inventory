<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('middle_name', 100)->nullable()->change();
            $table->string('contact_number', 100)->nullable()->change();
        });

        // Schema::table('inventory_items', function (Blueprint $table) {
        //     $table->string('some_column')->nullable()->change();
        // });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('middle_name', 100)->nullable(false)->change();
            $table->string('contact_number', 100)->nullable(false)->change();
        });

        // Schema::table('inventory_items', function (Blueprint $table) {
        //     $table->string('some_column')->nullable(false)->change();
        // });
    }
};