<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Free text for the "Others (Specify)" transfer type. The checked transfer
     * types themselves stay in acknowledgement_item_transfer_type.
     */
    public function up(): void
    {
        Schema::table('acknowledgement_items', function (Blueprint $table) {
            $table->string('transfer_type_other', 255)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('acknowledgement_items', function (Blueprint $table) {
            $table->dropColumn('transfer_type_other');
        });
    }
};
