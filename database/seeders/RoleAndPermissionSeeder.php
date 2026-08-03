<?php

namespace Database\Seeders;

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (PermissionEnum::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }

        $userRole = Role::findOrCreate(RoleEnum::User->value);
        $userRole->syncPermissions([
            PermissionEnum::ProfileRead->value,
            PermissionEnum::ProfileWrite->value,
            PermissionEnum::TokensManage->value,
        ]);

        $adminRole = Role::findOrCreate(RoleEnum::Admin->value);
        $adminRole->syncPermissions(PermissionEnum::values());
    }
}
