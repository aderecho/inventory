<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Borrowed from" is the staff member who handed the item over. The id lives
     * in released_by (defaulting to whoever is signed in) and the name is kept
     * here as a snapshot, so an old loan still reads correctly even if the staff
     * profile is later removed.
     */
    public function up(): void
    {
        Schema::table('item_borrowings', function (Blueprint $table) {
            $table->string('borrowed_from_name', 255)
                ->nullable()
                ->after('received_by');
        });
    }

    public function down(): void
    {
        Schema::table('item_borrowings', function (Blueprint $table) {
            $table->dropColumn('borrowed_from_name');
        });
    }
};

