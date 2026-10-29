<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $permission = 'archive disposal';

    /**
     * Archiving disposed property from the Disposal module is the same act as
     * archiving an item from the inventory, so the roles that can already do
     * that ("delete inventory") get it too.
     */
    public function up(): void
    {
        $now = now();

        DB::table('permissions')->insertOrIgnore([
            'name' => $this->permission,
            'guard_name' => 'web',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $permissionId = $this->permissionId();

        $sourceId = DB::table('permissions')
            ->where('name', 'delete inventory')
            ->where('guard_name', 'web')
            ->value('id');

        if (! $permissionId || ! $sourceId) {
            return;
        }

        foreach (DB::table('role_has_permissions')->where('permission_id', $sourceId)->pluck('role_id') as $roleId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }

        app()['cache']->forget('spatie.permission.cache');
    }

    public function down(): void
    {
        $permissionId = $this->permissionId();

        if (! $permissionId) {
            return;
        }

        DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
        DB::table('model_has_permissions')->where('permission_id', $permissionId)->delete();
        DB::table('permissions')->where('id', $permissionId)->delete();

        app()['cache']->forget('spatie.permission.cache');
    }

    private function permissionId(): ?int
    {
        return DB::table('permissions')
            ->where('name', $this->permission)
            ->where('guard_name', 'web')
            ->value('id');
    }
};
