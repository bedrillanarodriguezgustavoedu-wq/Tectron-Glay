<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('pedidos')) {
            Schema::create('pedidos', function (Blueprint $table): void {
                $table->id();
                $table->string('estado_orden');
                $table->decimal('total', 10, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    private function actingAsDashboardAdmin(): User
    {
        $permission = Permission::firstOrCreate([
            'name' => 'Ver dashboard del Admin',
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

    public function test_admin_dashboard_renders_with_sqlite_and_a_responsive_visual_summary(): void
    {
        $admin = $this->actingAsDashboardAdmin();
        $role = $admin->roles->first();
        foreach (['Crear Producto', 'Listado de Pedidos', 'Crear Oferta', 'Crear Banner'] as $name) {
            $permission = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
            $role->givePermissionTo($permission);
        }

        $this->get('/home')
            ->assertOk()
            ->assertSee('ADMINISTRACIÓN · APP GALY')
            ->assertSee('Accesos rápidos')
            ->assertSee('Nuevo producto')
            ->assertSee('Revisar pedidos')
            ->assertSee('Nueva oferta')
            ->assertSee('Nuevo banner')
            ->assertSee('href="' . route('admin.productos.create') . '"', false)
            ->assertSee('Analítica de la tienda')
            ->assertSee('col-12 col-lg-6', false)
            ->assertSee('col-12 col-md-6 col-xl-4', false);
    }

    public function test_galy_admin_dashboard_shows_the_shared_panel_link(): void
    {
        $admin = $this->actingAsDashboardAdmin();
        $admin->forceFill(['galy_user_id' => 12, 'galy_es_admin' => true])->save();
        Config::set('galy.public_url', 'http://localhost/galy-admin');

        $this->get('/home')
            ->assertOk()
            ->assertSee('Administrador compartido de GALY')
            ->assertSee('Gestiona productos, pedidos, inventario, banners y clientes compartidos con la app Android.')
            ->assertSee('href="' . route('admin.galy-panel') . '"', false)
            ->assertSee('Abrir administrador GALY');
    }

    public function test_galy_admin_dashboard_renders_the_panel_link_when_a_session_token_exists(): void
    {
        $admin = $this->actingAsDashboardAdmin();
        $admin->forceFill(['galy_user_id' => 12, 'galy_es_admin' => true])->save();

        $this->withSession(['galy_admin_token' => 'valid-admin-token'])
            ->get('/home')
            ->assertOk()
            ->assertSee('Abrir administrador GALY')
            ->assertSee('href="' . route('admin.galy-panel') . '"', false);
    }

    public function test_local_admin_can_start_linking_the_account_from_the_dashboard(): void
    {
        $this->actingAsDashboardAdmin();

        $this->get('/home')
            ->assertOk()
            ->assertSee('Conecta tu administrador con GALY')
            ->assertSee('Inicia sesión aquí con tu cuenta GALY para vincularla. GALY debe confirmar que eres administrador.')
            ->assertSee('href="' . route('web.login', ['galy' => 'connect']) . '"', false)
            ->assertDontSee('Accesos rápidos')
            ->assertSee('Conectar mi cuenta GALY');
    }

    public function test_galy_admin_can_open_the_shared_panel_using_a_temporary_link(): void
    {
        $admin = $this->actingAsDashboardAdmin();
        $admin->forceFill(['galy_user_id' => 12, 'galy_es_admin' => true])->save();
        Config::set('galy.admin_api_url', 'http://localhost/galy-admin/api/v1');
        Config::set('galy.public_url', 'http://localhost/galy-admin');
        Config::set('galy.sso_secret', 'secret-de-prueba-para-firmas-sso');
        Http::fake([
            'http://localhost/galy-admin/api/v1/auth/web-sso' => Http::response([
                'success' => true,
                'token' => 'temporary-token',
            ]),
        ]);

        $this->get(route('admin.galy-panel'))
            ->assertRedirect('http://localhost/galy-admin/login?sso_token=temporary-token');

        Http::assertSent(fn ($request) => $request->url() === 'http://localhost/galy-admin/api/v1/auth/web-sso'
            && $request['user_id'] === 12
            && is_string($request['signature'] ?? null));
    }

    public function test_non_galy_admin_cannot_open_the_shared_panel(): void
    {
        $this->actingAsDashboardAdmin();

        $this->get(route('admin.galy-panel'))
            ->assertForbidden();
    }

    public function test_dashboard_monthly_charts_use_only_the_current_year_and_stock_ranges(): void
    {
        $admin = $this->actingAsDashboardAdmin();

        $currentYearCustomer = User::factory()->create([
            'created_at' => now()->startOfMonth()->addDay(),
            'updated_at' => now()->startOfMonth()->addDay(),
        ]);
        User::factory()->create([
            'created_at' => now()->subYear(),
            'updated_at' => now()->subYear(),
        ]);

        $categoryId = Categoria::factory()->create()->id;
        foreach ([5, 40, 60] as $stock) {
            DB::table('productos')->insert([
                'categoria_id' => $categoryId,
                'nombre' => "Producto de prueba {$stock}",
                'codigo' => "TEST-STOCK-{$stock}",
                'descripcion_corta' => 'Producto de prueba',
                'descripcion_larga' => 'Producto de prueba',
                'precio_compra' => 10,
                'precio_venta' => 10,
                'stock' => $stock,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $html = $this->actingAs($admin)->get('/home')->assertOk()->getContent();

        preg_match('/const usuariosData = (\\[[^\\]]+\\]);/', $html, $usersMatch);
        $this->assertNotEmpty($usersMatch[1] ?? null);
        $monthlyUsers = json_decode($usersMatch[1], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(2, array_sum($monthlyUsers));
        $this->assertSame(2, $monthlyUsers[(int) $currentYearCustomer->created_at->format('n') - 1]);

        preg_match('/const porcentajeStockBajo = ([^;]+);/', $html, $lowStockMatch);
        preg_match('/const porcentajeStockAlto = ([^;]+);/', $html, $highStockMatch);

        $this->assertEqualsWithDelta(100 / 3, (float) ($lowStockMatch[1] ?? -1), 0.01);
        $this->assertEqualsWithDelta(100 / 3, (float) ($highStockMatch[1] ?? -1), 0.01);
    }
}
