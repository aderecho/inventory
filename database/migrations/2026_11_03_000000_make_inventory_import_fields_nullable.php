<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->unsignedInteger('supplier_id')->nullable()->change();
            $table->string('pr_number', 60)->nullable()->change();
            $table->string('po_number', 60)->nullable()->change();
        });

        Schema::table('acknowledgement_receipts', function (Blueprint $table) {
            $table->date('par_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->unsignedInteger('supplier_id')->nullable(false)->change();
            $table->string('pr_number', 60)->nullable(false)->change();
            $table->string('po_number', 60)->nullable(false)->change();
        });

        Schema::table('acknowledgement_receipts', function (Blueprint $table) {
            $table->date('par_date')->nullable(false)->change();
        });
    }
};
