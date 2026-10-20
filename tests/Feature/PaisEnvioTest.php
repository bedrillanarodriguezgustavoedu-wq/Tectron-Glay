<?php

namespace Tests\Feature;

use App\Models\Departamento;
use App\Models\Pais;
use App\Models\Provincia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaisEnvioTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsCountryAdmin(): User
    {
        $permission = Permission::firstOrCreate([
            'name' => 'Gestionar países de envío',
            'guard_name' => 'web',
        ]);
        $role = Role::firstOrCreate([
            'name' => 'ADMINISTRADOR',
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo($permission);

        $admin = User::factory()->create();
        $admin->assignRole($role);
        $this->actingAs($admin);

        return $admin;
    }

    public function test_country_admin_page_shows_existing_zones_and_saved_address_usage(): void
    {
        $this->actingAsCountryAdmin();
        $peru = Pais::where('nombre', 'Perú')->firstOrFail();

        $this->get(route('admin.paises.index'))
            ->assertOk()
            ->assertSee('Países de envío')
            ->assertSee('Perú')
            ->assertSee((string) $peru->departamentos()->count())
            ->assertSee('único país configurado');
    }

    public function test_country_with_geographic_zones_cannot_be_deleted(): void
    {
        $this->actingAsCountryAdmin();
        $peru = Pais::where('nombre', 'Perú')->firstOrFail();

        $this->delete(route('admin.paises.destroy', $peru->id))
            ->assertRedirect(route('admin.paises.index'))
            ->assertSessionHasErrors('pais');

        $this->assertDatabaseHas('paises', ['id' => $peru->id, 'nombre' => 'Perú']);
    }

    public function test_new_countries_are_created_disabled_by_default_when_shipping_is_not_requested(): void
    {
        $this->actingAsCountryAdmin();

        $this->post(route('admin.paises.store'), ['nombre' => '  Chile  '])
            ->assertRedirect();

        $this->assertDatabaseHas('paises', [
            'nombre' => 'Chile',
            'activo' => false,
            'realiza_envios' => false,
        ]);
    }

    public function test_customer_cannot_save_a_department_or_province_from_another_country(): void
    {
        $customer = User::factory()->create();
        $peru = Pais::where('nombre', 'Perú')->firstOrFail();
        $otherCountry = Pais::create([
            'nombre' => 'País de prueba',
            'activo' => true,
            'realiza_envios' => true,
        ]);
        $department = Departamento::where('pais_id', $peru->id)->firstOrFail();
        $province = Provincia::where('departamento_id', $department->id)->firstOrFail();

        $this->actingAs($customer)
            ->post(route('web.direcciones.store'), [
                'alias' => 'Casa',
                'pais_id' => $otherCountry->id,
                'departamento_id' => $department->id,
                'provincia_id' => $province->id,
                'direccion_exacta' => 'Calle de prueba 1',
            ])
            ->assertSessionHasErrors('departamento_id');

        $this->assertDatabaseCount('direccions', 0);
    }

    public function test_customer_can_save_a_valid_peru_address(): void
    {
        $customer = User::factory()->create();
        $peru = Pais::where('nombre', 'Perú')->firstOrFail();
        $department = Departamento::where('pais_id', $peru->id)->firstOrFail();
        $province = Provincia::where('departamento_id', $department->id)->firstOrFail();

        $this->actingAs($customer)
            ->post(route('web.direcciones.store'), [
                'alias' => 'Casa',
                'pais_id' => $peru->id,
                'departamento_id' => $department->id,
                'provincia_id' => $province->id,
                'direccion_exacta' => 'Calle de prueba 1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('direccions', [
            'usuario_id' => $customer->id,
            'pais_id' => $peru->id,
            'departamento_id' => $department->id,
            'provincia_id' => $province->id,
        ]);
    }

    public function test_inactive_or_non_shipping_countries_return_no_departments(): void
    {
        $peru = Pais::where('nombre', 'Perú')->firstOrFail();
        $peru->update(['activo' => false]);

        $this->getJson('/api/paises/'.$peru->id.'/departamentos')->assertOk()->assertExactJson([]);
    }
}
