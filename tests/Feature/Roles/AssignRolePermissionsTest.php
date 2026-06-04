<?php

namespace Tests\Feature\Roles;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssignRolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_permission_seeder_prefills_operational_roles(): void
    {
        Permission::findOrCreate('legacy.permission');
        Permission::findOrCreate('companies.delete');
        $manager = Role::findOrCreate('manager');
        $gerencia = Role::query()->create(['name' => ' Gerencia ', 'guard_name' => 'web']);
        Role::findOrCreate('gerente');
        Role::findOrCreate('empresa_pendiente');
        $managerUser = User::factory()->create();
        $gerenciaUser = User::factory()->create();
        $managerUser->assignRole($manager);
        $gerenciaUser->assignRole($gerencia);
        $manager->givePermissionTo('companies.delete');

        $this->seed(RolePermissionSeeder::class);

        $gerentePermissions = [
            'dashboard.view',
            'users.view',
            'users.create',
            'users.edit',
            'users.change-password',
            'companies.view',
            'companies.update',
            'tours.view',
            'tours.create',
            'tours.edit',
            'bookings.view',
            'bookings.manage',
            'website.manage',
            'subscription.view',
            'credits.view',
            'credits.purchase',
        ];

        $pendingCompanyPermissions = [
            'dashboard.view',
            'companies.view',
            'companies.update',
            'registration_requests.resubmit',
        ];

        $this->assertDatabaseMissing('roles', ['name' => 'manager', 'guard_name' => 'web']);
        $this->assertDatabaseMissing('roles', ['name' => ' Gerencia ', 'guard_name' => 'web']);
        $this->assertTrue(Role::findByName('gerente')->hasAllPermissions($gerentePermissions));
        $this->assertTrue(Role::findByName('gerente')->hasPermissionTo('companies.delete'));
        $this->assertTrue($managerUser->fresh()->hasRole('gerente'));
        $this->assertTrue($gerenciaUser->fresh()->hasRole('gerente'));
        $this->assertTrue(Role::findByName('empresa_pendiente')->hasAllPermissions($pendingCompanyPermissions));
        $this->assertTrue(Role::findByName('registration_applicant')->hasAllPermissions($pendingCompanyPermissions));
        $this->assertDatabaseHas('permissions', ['name' => 'legacy.permission']);
    }

    public function test_role_and_permission_labels_are_displayed_in_spanish_without_changing_values(): void
    {
        $actor = User::factory()->create();
        Permission::findOrCreate('roles.assign-permissions');
        Permission::findOrCreate('companies.view');
        $actor->givePermissionTo('roles.assign-permissions');

        $role = Role::findOrCreate('manager');
        $role->givePermissionTo('companies.view');

        $response = $this
            ->actingAs($actor)
            ->get(route('roles.permissions.form', $role));

        $response->assertOk();
        $response->assertSee('Gerente');
        $response->assertSee('Empresas');
        $response->assertSee('Empresas: Ver');
        $response->assertSee('value="companies.view"', false);
    }

    public function test_role_permissions_can_be_saved_without_role_name(): void
    {
        $actor = User::factory()->create();
        Permission::findOrCreate('roles.assign-permissions');
        $actor->givePermissionTo('roles.assign-permissions');

        $role = Role::findOrCreate('manager');
        Permission::findOrCreate('companies.view');

        $response = $this
            ->actingAs($actor)
            ->patch(route('roles.permissions', $role), [
                'permissions' => ['companies.view'],
            ]);

        $response->assertRedirect(route('roles.index'));
        $this->assertTrue($role->fresh()->hasPermissionTo('companies.view'));
    }

    public function test_all_company_permissions_can_be_removed_from_super_admin(): void
    {
        $actor = User::factory()->create();
        $requiredPermissions = [
            'roles.assign-permissions',
            'roles.view',
            'roles.edit',
        ];
        $companyPermissions = [
            'companies.view',
            'companies.create',
            'companies.update',
            'companies.delete',
        ];

        foreach ([...$requiredPermissions, ...$companyPermissions] as $permission) {
            Permission::findOrCreate($permission);
        }

        $actor->givePermissionTo('roles.assign-permissions');

        $role = Role::findOrCreate('super_admin');
        $role->syncPermissions([...$requiredPermissions, ...$companyPermissions]);

        $response = $this
            ->actingAs($actor)
            ->patch(route('roles.permissions', $role), [
                'permissions' => $requiredPermissions,
            ]);

        $response->assertRedirect(route('roles.index'));

        $role->refresh();
        $this->assertTrue($role->hasAllPermissions($requiredPermissions));
        $this->assertFalse($role->hasAnyPermission($companyPermissions));
    }
}
