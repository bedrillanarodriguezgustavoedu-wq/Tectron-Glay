<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CuponAdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('cupones', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->integer('descuento_porcentaje');
            $table->date('fecha_expiracion')->nullable();
            $table->boolean('activo')->default(true);
        });
    }

    private function adminConPermisos(array $permisos): User
    {
        $role = Role::firstOrCreate(['name' => 'SUPER ADMIN', 'guard_name' => 'web']);

        foreach ($permisos as $nombre) {
            $role->givePermissionTo(Permission::firstOrCreate([
                'name' => $nombre,
                'guard_name' => 'web',
            ]));
        }

        $admin = User::factory()->create();
        $admin->assignRole($role);
        $this->actingAs($admin);

        return $admin;
    }

    public function test_admin_sees_the_coupons_the_app_already_uses(): void
    {
        $this->adminConPermisos(['Listado de Cupones']);

        DB::table('cupones')->insert([
            ['codigo' => 'BIENVENIDA', 'descuento_porcentaje' => 10, 'fecha_expiracion' => '2030-12-31', 'activo' => 1],
            ['codigo' => 'XNOVATEC', 'descuento_porcentaje' => 20, 'fecha_expiracion' => null, 'activo' => 1],
        ]);

        $this->get('/admin/cupones')
            ->assertSuccessful()
            ->assertSee('BIENVENIDA')
            ->assertSee('XNOVATEC')
            ->assertSee('Cupones');
    }

    public function test_a_lowercase_code_is_stored_uppercase_because_the_app_looks_it_up_uppercase(): void
    {
        $this->adminConPermisos(['Listado de Cupones', 'Crear Cupon', 'Guardar Cupon']);

        $this->post('/admin/cupones/create', [
            'codigo' => '  navidad25 ',
            'descuento_porcentaje' => '15',
            'fecha_expiracion' => '2027-01-06',
            'activo' => '1',
        ])->assertRedirect(route('admin.cupones.index'));

        $this->assertDatabaseHas('cupones', [
            'codigo' => 'NAVIDAD25',
            'descuento_porcentaje' => 15,
            'activo' => 1,
        ]);
    }

    public function test_a_full_discount_or_a_messy_code_is_rejected(): void
    {
        $this->adminConPermisos(['Listado de Cupones', 'Crear Cupon', 'Guardar Cupon']);

        $this->from('/admin/cupones/create')
            ->post('/admin/cupones/create', [
                'codigo' => 'TODO GRATIS',
                'descuento_porcentaje' => '100',
            ])
            ->assertSessionHasErrors(['codigo', 'descuento_porcentaje']);

        $this->assertDatabaseMissing('cupones', ['codigo' => 'TODOGRATIS']);
    }

    public function test_an_existing_code_cannot_be_repeated(): void
    {
        $this->adminConPermisos(['Listado de Cupones', 'Crear Cupon', 'Guardar Cupon']);

        DB::table('cupones')->insert([
            ['codigo' => 'GALY2026', 'descuento_porcentaje' => 15, 'fecha_expiracion' => null, 'activo' => 1],
        ]);

        $this->post('/admin/cupones/create', [
            'codigo' => 'galy2026',
            'descuento_porcentaje' => 5,
        ])->assertSessionHasErrors('codigo');
    }

    public function test_removing_a_coupon_pauses_it_and_keeps_the_row_for_orders_already_paid(): void
    {
        $this->adminConPermisos(['Listado de Cupones', 'Eliminar Cupon']);

        $id = DB::table('cupones')->insertGetId([
            'codigo' => 'GALY2025',
            'descuento_porcentaje' => 10,
            'fecha_expiracion' => null,
            'activo' => 1,
        ]);

        $this->delete('/admin/cupon/'.$id)->assertRedirect(route('admin.cupones.index'));

        $this->assertDatabaseHas('cupones', ['id' => $id, 'activo' => 0]);
    }

    public function test_an_expired_coupon_is_reported_as_vencido_and_a_future_one_as_vigente(): void
    {
        $this->adminConPermisos(['Listado de Cupones']);

        DB::table('cupones')->insert([
            ['codigo' => 'VIEJO', 'descuento_porcentaje' => 10, 'fecha_expiracion' => now()->subDay()->toDateString(), 'activo' => 1],
            ['codigo' => 'HOY', 'descuento_porcentaje' => 10, 'fecha_expiracion' => now()->toDateString(), 'activo' => 1],
        ]);

        $this->get('/admin/cupones')
            ->assertSee('Vencido')
            ->assertSee('Vigente');
    }

    public function test_a_customer_cannot_reach_the_coupon_panel(): void
    {
        $this->adminConPermisos([]);

        $cliente = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'CLIENTE', 'guard_name' => 'web']);
        $cliente->assignRole($role);
        $this->actingAs($cliente);

        $this->get('/admin/cupones')->assertForbidden();
        $this->post('/admin/cupones/create')->assertForbidden();
    }
}
