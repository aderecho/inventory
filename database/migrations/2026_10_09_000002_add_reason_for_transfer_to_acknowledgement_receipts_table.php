<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Reason/s for Transfer" printed on the ITR and PTR. Only filled for reassignments.
     */
    public function up(): void
    {
        Schema::table('acknowledgement_receipts', function (Blueprint $table) {
            $table->text('reason_for_transfer')->nullable()->after('remarks');
        });
    }

    public function down(): void
    {
        Schema::table('acknowledgement_receipts', function (Blueprint $table) {
            $table->dropColumn('reason_for_transfer');
        });
    }
};
