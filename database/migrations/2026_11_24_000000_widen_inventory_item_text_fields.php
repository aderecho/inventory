<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->string('fund_source', 255)->nullable()->change();
            $table->string('po_number', 255)->nullable()->change();
            $table->string('pr_number', 255)->nullable()->change();
            $table->string('serial_number', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        $hasLongReferenceNumber = DB::table('inventory_items')
            ->where(function ($query) {
                $query->whereRaw('LENGTH(fund_source) > 50')
                    ->orWhereRaw('LENGTH(po_number) > 60')
                    ->orWhereRaw('LENGTH(pr_number) > 60');
            })
            ->exists();

        if ($hasLongReferenceNumber) {
            throw new \RuntimeException('Cannot shrink fund source or PO/PR columns while values exceed their previous limits.');
        }

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->string('fund_source', 50)->nullable()->change();
            $table->string('po_number', 60)->nullable()->change();
            $table->string('pr_number', 60)->nullable()->change();
            $table->string('serial_number', 255)->nullable()->change();
        });
    }
};
