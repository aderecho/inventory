<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The Item Evidence feature is gone: items are approved from the holder's
     * own dashboard and the approval list only reads inventory_items.approval_status.
     * Its upload table has no readers left, so drop it.
     */
    public function up(): void
    {
        Schema::dropIfExists('inventory_item_files');
    }

    /**
     * The feature was removed, so there is no table to recreate. Restoring the
     * evidence uploads would mean bringing back its model, controller and views
     * as well, which this migration deliberately does not do.
     */
    public function down(): void
    {
        //
    }
};
