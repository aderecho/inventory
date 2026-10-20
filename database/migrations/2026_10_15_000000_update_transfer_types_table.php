<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The transfer types the PAR/ITR forms offer: Donation, Relocate and
     * Reassignment, plus the free-text "Others (specify)". "New Assignment" is
     * retired, so it stops being offered and no longer prints as a type.
     */
    public function up(): void
    {
        $now = now();

        DB::table('transfer_types')->insertOrIgnore([
            ['name' => 'Donation', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Relocate', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // Items still carrying the retired type lose the link with it, so no
        // record keeps pointing at a type the forms no longer show.
        DB::table('transfer_types')->where('name', 'New Assignment')->delete();
    }

    public function down(): void
    {
        DB::table('transfer_types')->whereIn('name', ['Donation', 'Relocate'])->delete();

        $now = now();

        DB::table('transfer_types')->insertOrIgnore([
            ['name' => 'New Assignment', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
};
