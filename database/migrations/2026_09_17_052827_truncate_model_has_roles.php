<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('model_has_roles')->truncate();

        DB::table('model_has_roles')->insert([
            'role_id' => 6,
            'model_type' => 'App\\Models\\User',
            'model_id' => 2362,
        ]);
    }

    public function down(): void
    {
        DB::table('model_has_roles')
            ->where('role_id', 6)
            ->where('model_type', 'App\\Models\\User')
            ->where('model_id', 2362)
            ->delete();
    }
};