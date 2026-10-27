<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private string $role = 'STUDENT';

    /**
     * Students only use their portal, where they see the items they have
     * borrowed. Like USER, the role carries no permissions, so every admin page
     * stays closed.
     */
    public function up(): void
    {
        DB::table('roles')->insertOrIgnore([
            'name' => $this->role,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Role assignments and permissions cascade with the role.
        DB::table('roles')
            ->where('name', $this->role)
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
