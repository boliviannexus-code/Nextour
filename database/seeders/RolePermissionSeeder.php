<?php

namespace Database\Seeders;

use RuntimeException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $guard = 'web';

        $permissions = [
            'dashboard.view',
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.restore',
            'users.change-password',
            'users.assign-roles',
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'roles.assign-permissions',
            'permissions.view',
            'permissions.create',
            'permissions.edit',
            'permissions.delete',
            'companies.view',
            'companies.create',
            'companies.update',
            'companies.delete',
            'categories.view',
            'categories.create',
            'categories.update',
            'categories.delete',
            'guide_types.view',
            'guide_types.create',
            'guide_types.update',
            'guide_types.delete',
            'transport_types.view',
            'transport_types.create',
            'transport_types.update',
            'transport_types.delete',
            'activity_types.view',
            'activity_types.create',
            'activity_types.update',
            'activity_types.delete',
            'tours.view',
            'tours.create',
            'tours.edit',
            'tours.delete',
            'tours.review',
            'tours.pricing',
            'tours.availability',
            'bookings.view',
            'bookings.manage',
            'website.manage',
            'audits.view',
            'registration_requests.resubmit',
            'subscription.view',
            'subscription.manage',
            'credits.view',
            'credits.manage',
            'credits.adjust',
            'credits.purchase',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => $guard,
            ]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissionModels = Permission::query()
            ->where('guard_name', $guard)
            ->whereIn('name', $permissions)
            ->get()
            ->keyBy('name');

        $missingPermissions = array_values(array_diff($permissions, $permissionModels->keys()->all()));

        if ($missingPermissions !== []) {
            throw new RuntimeException('No se pudieron crear los permisos: '.implode(', ', $missingPermissions));
        }

        $syncRolePermissions = function (string $role, array $permissions) use ($guard, $permissionModels): void {
            $roleModel = Role::findOrCreate($role, $guard);
            $uniquePermissions = array_values(array_unique($permissions));
            $permissionCollection = collect($uniquePermissions)
                ->map(fn (string $permission) => $permissionModels->get($permission))
                ->filter()
                ->values();

            if ($permissionCollection->count() !== count($uniquePermissions)) {
                $found = $permissionCollection->pluck('name')->all();
                $missing = array_values(array_diff($uniquePermissions, $found));

                throw new RuntimeException("El rol {$role} no puede sincronizarse. Faltan permisos: ".implode(', ', $missing));
            }

            $roleModel->syncPermissions($permissionCollection);
        };

        Role::findOrCreate('super_admin', $guard)->syncPermissions($permissionModels->values());
        Role::findOrCreate('admin', $guard)->syncPermissions($permissionModels->values());

        $companyMonetizationPermissions = [
            'subscription.view',
            'credits.view',
            'credits.purchase',
        ];

        $managerPermissions = [
            'dashboard.view',
            'users.view',
            'users.create',
            'users.edit',
            'users.change-password',
            'roles.view',
            'permissions.view',
            'companies.view',
            'companies.update',
            'categories.view',
            'categories.create',
            'categories.update',
            'categories.delete',
            'guide_types.view',
            'guide_types.create',
            'guide_types.update',
            'guide_types.delete',
            'transport_types.view',
            'transport_types.create',
            'transport_types.update',
            'transport_types.delete',
            'activity_types.view',
            'activity_types.create',
            'activity_types.update',
            'activity_types.delete',
            'tours.view',
            'tours.create',
            'tours.edit',
            'tours.delete',
            'tours.pricing',
            'tours.availability',
            'bookings.view',
            'bookings.manage',
            'website.manage',
            'audits.view',
        ];

        $managerPermissions = array_values(array_unique(array_merge(
            $managerPermissions,
            $companyMonetizationPermissions,
        )));

        $gerente = $this->mergeGerenteRoles($guard);
        $existingGerentePermissions = $gerente->permissions()->pluck('name')->all();
        $forbiddenGerentePermissions = [
            'subscription.manage',
            'credits.manage',
            'credits.adjust',
            'users.delete',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'roles.assign-permissions',
            'permissions.create',
            'permissions.edit',
            'permissions.delete',
        ];
        $syncRolePermissions('gerente', array_values(array_diff(
            array_unique(array_merge($existingGerentePermissions, $managerPermissions)),
            $forbiddenGerentePermissions,
        )));

        $viewerPermissions = [
            'dashboard.view',
            'users.view',
            'roles.view',
            'permissions.view',
            'companies.view',
            'categories.view',
            'guide_types.view',
            'transport_types.view',
            'activity_types.view',
            'tours.view',
            'bookings.view',
            'audits.view',
            'subscription.view',
            'credits.view',
        ];

        $syncRolePermissions('viewer', $viewerPermissions);

        $pendingCompanyPermissions = [
            'dashboard.view',
            'companies.view',
            'companies.update',
            'registration_requests.resubmit',
        ];

        $syncRolePermissions('empresa_pendiente', $pendingCompanyPermissions);
        $syncRolePermissions('registration_applicant', $pendingCompanyPermissions);

        Role::findOrCreate('tourist', $guard)->syncPermissions([]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    private function mergeGerenteRoles(string $guard): Role
    {
        $canonical = Role::findOrCreate('gerente', $guard);
        $aliases = ['manager', 'gerente', 'gerencia'];

        $duplicates = Role::query()
            ->where('guard_name', $guard)
            ->get()
            ->filter(function (Role $role) use ($aliases): bool {
                $normalized = mb_strtolower(trim($role->name));

                return in_array($normalized, $aliases, true);
            });

        foreach ($duplicates as $duplicate) {
            if ((int) $duplicate->id === (int) $canonical->id) {
                continue;
            }

            $roleHasPermissionsTable = config('permission.table_names.role_has_permissions');
            $modelHasRolesTable = config('permission.table_names.model_has_roles');
            $rolePivotKey = app(PermissionRegistrar::class)->pivotRole;

            $this->copyPivotRows($roleHasPermissionsTable, $rolePivotKey, (int) $duplicate->id, (int) $canonical->id);
            $this->copyPivotRows($modelHasRolesTable, $rolePivotKey, (int) $duplicate->id, (int) $canonical->id);

            DB::table($roleHasPermissionsTable)->where($rolePivotKey, $duplicate->id)->delete();
            DB::table($modelHasRolesTable)->where($rolePivotKey, $duplicate->id)->delete();
            $duplicate->delete();
        }

        return $canonical->refresh();
    }

    private function copyPivotRows(string $table, string $key, int $fromId, int $toId): void
    {
        $rows = DB::table($table)
            ->where($key, $fromId)
            ->get()
            ->map(function (object $row) use ($key, $toId): array {
                $data = (array) $row;
                $data[$key] = $toId;

                return $data;
            })
            ->all();

        if ($rows !== []) {
            DB::table($table)->insertOrIgnore($rows);
        }
    }
}
