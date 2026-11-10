<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'free_text_property_number')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('free_text_property_number');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'free_text_property_number')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('free_text_property_number')
                    ->default(false)
                    ->after('status');
            });
        }
    }
};
