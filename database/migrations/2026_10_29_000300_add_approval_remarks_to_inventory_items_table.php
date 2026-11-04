<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Why the accountable person rejected the item. Kept apart from the item's
     * own "remarks" (typed when the item is created and printed on the IIRUP).
     */
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->text('approval_remarks')->nullable()->after('approval_status');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn('approval_remarks');
        });
    }
};
