<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What a condition means to the process, independent of what it is called:
     * serviceable, a disposal candidate, or already disposed of.
     */
    public function up(): void
    {
        Schema::table('asset_conditions', function (Blueprint $table) {
            $table->string('disposition', 20)
                ->default('serviceable')
                ->after('description')
                ->index();
        });

        // Existing catalogue: only the disposal row is special. It also gets the
        // label the system now asks for, unless that name is already taken.
        $disposed = DB::table('asset_conditions')
            ->where('condition_name', 'like', '%dispos%')
            ->orderBy('id')
            ->first();

        if ($disposed) {
            DB::table('asset_conditions')
                ->where('id', $disposed->id)
                ->update(['disposition' => 'disposed']);

            $nameTaken = DB::table('asset_conditions')
                ->where('condition_name', 'Disposed')
                ->exists();

            if (! $nameTaken) {
                DB::table('asset_conditions')
                    ->where('id', $disposed->id)
                    ->update(['condition_name' => 'Disposed']);
            }

            // One-off backfill: items already inspected as disposed take the
            // status the app now reads, instead of being recognised by name.
            $latestInspectionSaysDisposed = DB::table('asset_inspections as inspection')
                ->select('inspection.inventory_item_id')
                ->whereIn('inspection.asset_condition_id', function ($query) {
                    $query->select('id')
                        ->from('asset_conditions')
                        ->where('disposition', 'disposed');
                })
                ->whereRaw(
                    'inspection.inspection_date = (select max(inner_inspection.inspection_date)'
                    . ' from asset_inspections as inner_inspection'
                    . ' where inner_inspection.inventory_item_id = inspection.inventory_item_id)'
                );

            DB::table('inventory_items')
                ->whereIn('id', $latestInspectionSaysDisposed)
                ->where('status', '!=', 'borrowed')
                ->update(['status' => 'disposed']);
        }
    }

    public function down(): void
    {
        $renamed = DB::table('asset_conditions')
            ->where('condition_name', 'Disposed')
            ->where('disposition', 'disposed')
            ->orderBy('id')
            ->first();

        $previousNameTaken = DB::table('asset_conditions')
            ->where('condition_name', 'Disposal')
            ->exists();

        if ($renamed && ! $previousNameTaken) {
            DB::table('asset_conditions')
                ->where('id', $renamed->id)
                ->update(['condition_name' => 'Disposal']);
        }

        Schema::table('asset_conditions', function (Blueprint $table) {
            $table->dropIndex(['disposition']);
            $table->dropColumn('disposition');
        });
    }
};
