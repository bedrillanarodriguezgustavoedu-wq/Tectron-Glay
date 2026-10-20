<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdminWithPermissions(array $permissionNames): User
    {
        $role = Role::firstOrCreate(['name' => 'SUPER ADMIN', 'guard_name' => 'web']);
        foreach ($permissionNames as $name) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }

        $admin = User::factory()->create();
        $admin->assignRole($role);
        $this->actingAs($admin);

        return $admin;
    }

    public function test_super_admin_role_cannot_be_renamed_deleted_or_have_permissions_changed(): void
    {
        $this->actingAsAdminWithPermissions([
            'Editar Rol',
            'Actualizar Rol',
            'Eliminar Rol',
            'Ver detalle de Permisos de rol',
            'Actualizar Permisos de rol',
        ]);
        $superAdminRole = Role::findByName('SUPER ADMIN', 'web');

        $this->get(route('admin.roles.edit', $superAdminRole->id))->assertForbidden();
        $this->get(route('admin.roles.permisos', $superAdminRole->id))->assertForbidden();
        $this->put(route('admin.roles.update', $superAdminRole->id), ['name' => 'SUPERVISOR'])->assertForbidden();
        $this->put(route('admin.roles.update_permisos', $superAdminRole->id), ['permisos' => []])->assertForbidden();
        $this->delete(route('admin.roles.destroy', $superAdminRole->id))->assertForbidden();

        $this->assertSame('SUPER ADMIN', $superAdminRole->fresh()->name);
    }

    public function test_role_names_are_normalized_and_permissions_are_validated_and_saved(): void
    {
        $this->actingAsAdminWithPermissions(['Guardar Rol', 'Actualizar Permisos de rol']);

        $this->post(route('admin.roles.store'), ['name' => '  supervisor  '])
            ->assertRedirect(route('admin.roles.index'));

        $role = Role::findByName('SUPERVISOR', 'web');
        $permission = Permission::create(['name' => 'Gestionar productos de prueba', 'guard_name' => 'web']);

        $this->put(route('admin.roles.update_permisos', $role->id), [
            'permisos' => [$permission->id],
        ])->assertRedirect(route('admin.roles.index'));
        $this->assertTrue($role->fresh()->hasPermissionTo($permission));

        $this->put(route('admin.roles.update_permisos', $role->id), [
            'permisos' => [999999],
        ])->assertSessionHasErrors('permisos.0');
        $this->assertTrue($role->fresh()->hasPermissionTo($permission));
    }

    public function test_assigned_roles_cannot_be_deleted(): void
    {
        $this->actingAsAdminWithPermissions(['Eliminar Rol']);
        $role = Role::create(['name' => 'CAJERO', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->delete(route('admin.roles.destroy', $role->id))
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'CAJERO']);
    }

    public function test_role_pages_show_usage_and_group_permissions_by_module(): void
    {
        $this->actingAsAdminWithPermissions([
            'Listado de Roles',
            'Ver detalle de Permisos de rol',
        ]);
        $role = Role::create(['name' => 'VENDEDOR', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'Ver Reservas', 'guard_name' => 'web']));
        User::factory()->create()->assignRole($role);

        $this->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('Roles y permisos')
            ->assertSee('VENDEDOR')
            ->assertSee('Protegido');

        $this->get(route('admin.roles.permisos', $role->id))
            ->assertOk()
            ->assertSee('Permisos: VENDEDOR')
            ->assertSee('Reservas')
            ->assertSee('Ver Reservas');
    }

    public function test_admin_requests_cannot_assign_super_admin_role_to_a_user(): void
    {
        $this->actingAsAdminWithPermissions(['Guardar Usuario', 'Actualizar Usuario']);
        Role::firstOrCreate(['name' => 'ADMINISTRADOR', 'guard_name' => 'web']);
        $target = User::factory()->create();
        $target->assignRole(Role::create(['name' => 'CLIENTE', 'guard_name' => 'web']));

        $this->post(route('admin.usuarios.store'), [
            'rol' => 'SUPER ADMIN',
            'name' => 'Usuario de prueba',
            'email' => 'usuario.rol@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('rol');

        $this->put(route('admin.usuarios.update', $target->id), [
            'rol' => 'SUPER ADMIN',
            'name' => $target->name,
            'email' => $target->email,
        ])->assertSessionHasErrors('rol');

        $this->assertDatabaseMissing('users', ['email' => 'usuario.rol@example.test']);
        $this->assertTrue($target->fresh()->hasRole('CLIENTE'));
    }
}
