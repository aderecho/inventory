<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The PAR spreadsheet has descriptions well past 255 characters, several
     * serial numbers per item, and PAR numbers longer than 20 characters.
     */
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
            $table->string('serial_number', 255)->nullable()->change();
        });

        Schema::table('acknowledgement_receipts', function (Blueprint $table) {
            $table->string('category', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->string('description', 255)->nullable()->change();
            $table->string('serial_number', 80)->nullable()->change();
        });

        Schema::table('acknowledgement_receipts', function (Blueprint $table) {
            $table->string('category', 20)->nullable()->change();
        });
    }
};
