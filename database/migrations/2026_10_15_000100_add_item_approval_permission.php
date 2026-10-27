<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $permission = 'view item approvals';

    /**
     * The Item Evidence page became the Item Approval list, so it needs its
     * own permission. Every role that could open the evidence list keeps
     * access to the approval list.
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

        if (! $permissionId) {
            return;
        }

        $sourceId = DB::table('permissions')
            ->where('name', 'view acknowledgements')
            ->where('guard_name', 'web')
            ->value('id');

        if (! $sourceId) {
            return;
        }

        $roleIds = DB::table('role_has_permissions')
            ->where('permission_id', $sourceId)
            ->pluck('role_id');

        foreach ($roleIds as $roleId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }

    public function down(): void
    {
        $permissionId = $this->permissionId();

        if (! $permissionId) {
            return;
        }

        DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
        DB::table('permissions')->where('id', $permissionId)->delete();
    }

    private function permissionId(): ?int
    {
        return DB::table('permissions')
            ->where('name', $this->permission)
            ->where('guard_name', 'web')
            ->value('id');
    }
};
