<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        $user = User::find(34);

        if (!$user) {
            return;
        }

        $permissions = [
            'view permissions',
            'edit permissions',
            'delete permissions',
        ];

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);

            $user->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        $user = User::find(34);

        if (!$user) {
            return;
        }

        $permissions = [
            'view permissions',
            'edit permissions',
            'delete permissions',
        ];

        foreach ($permissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)
                ->where('guard_name', 'web')
                ->first();

            if ($permission) {
                $user->revokePermissionTo($permission);
            }
        }
    }
};