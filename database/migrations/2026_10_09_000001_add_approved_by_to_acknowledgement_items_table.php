<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Who approved a reassignment (the "Approved by" signatory on the Inventory
     * Transfer Report). Stays null for first assignments.
     */
    public function up(): void
    {
        Schema::table('acknowledgement_items', function (Blueprint $table) {
            $table->unsignedInteger('approved_by')->nullable()->after('issued_by_id');

            $table->foreign('approved_by')
                ->references('id')->on('user_profiles')
                ->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('acknowledgement_items', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn('approved_by');
        });
    }
};
