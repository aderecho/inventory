<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('employee_number', 50)->nullable()->unique()->after('user_id');
            $table->string('title_name', 50)->nullable()->after('employee_number');
            $table->string('ext_name', 50)->nullable()->after('last_name');
            $table->string('primary_unit_division_department', 255)
                ->nullable()
                ->after('ext_name');
            $table->string('employee_primary_unit_college', 255)
                ->nullable()
                ->after('primary_unit_division_department');
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropUnique(['employee_number']);

            $table->dropColumn([
                'employee_number',
                'title_name',
                'ext_name',
                'primary_unit_division_department',
                'employee_primary_unit_college',
            ]);
        });
    }
};
