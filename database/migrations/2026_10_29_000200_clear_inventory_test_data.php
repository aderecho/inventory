<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One-off clean-up before going live: removes every inventory item and all the
 * test records hanging off it, and resets their ids so production starts at 1.
 *
 * The six tables asked for, plus the rows that only exist under them, so no
 * orphans are left behind:
 *  - inventory_item_files: attachment rows of acknowledgement items
 *  - inventory_transactions: per-item transaction rows
 *  - item_borrowing_items, and item_borrowings: loans of the deleted items
 *
 * Not touched: users, profiles, suppliers, categories, asset conditions,
 * transfer types, rooms, roles/permissions, the activity log, and any uploaded
 * files on disk.
 *
 * This cannot be undone. Back up the database before running it.
 */
return new class extends Migration
{
    /** Children first, so nothing references a row that is already gone. */
    private array $tables = [
        'acknowledgement_item_transfer_type',
        'inventory_item_files',
        'acknowledgement_items',
        'acknowledgement_receipts',
        'asset_inspections',
        'item_history_location',
        'inventory_transactions',
        'item_borrowing_items',
        'item_borrowings',
        'inventory_items',
    ];

    public function up(): void
    {
        $tables = array_values(array_filter($this->tables, fn (string $table) => Schema::hasTable($table)));

        // TRUNCATE resets the ids but MySQL refuses it on tables other tables
        // point at, so foreign key checks are paused only for these statements.
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            try {
                foreach ($tables as $table) {
                    DB::table($table)->truncate();
                }
            } finally {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }

            return;
        }

        // SQLite (the test suite): plain deletes in the same order.
        foreach ($tables as $table) {
            DB::table($table)->delete();
        }
    }

    /** Deleted data cannot be brought back; restore from a backup instead. */
    public function down(): void
    {
    }
};
