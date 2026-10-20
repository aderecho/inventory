<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // FOREIGN_KEY_CHECKS is MySQL-only; the test suite migrates on SQLite.
        $usesForeignKeyChecks = DB::connection()->getDriverName() === 'mysql';

        if ($usesForeignKeyChecks) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        DB::table('acknowledgement_items')->truncate();
        DB::table('acknowledgement_receipts')->truncate();
        DB::table('inventory_items')->truncate();
        DB::table('activity_log')->truncate();
        DB::table('asset_inspections')->truncate();
        DB::table('item_history_location')->truncate();

        if ($usesForeignKeyChecks) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
    }
};
