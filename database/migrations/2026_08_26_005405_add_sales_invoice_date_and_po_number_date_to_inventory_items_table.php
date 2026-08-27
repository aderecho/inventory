<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->date('sales_invoice_date')
                ->nullable()
                ->after('invoice');

            $table->date('po_number_date')
                ->nullable()
                ->after('po_number');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn([
                'sales_invoice_date',
                'po_number_date',
            ]);
        });
    }
};