<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AppGalyAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $galy = Schema::connection('galy');

        foreach (['pedido_productos', 'comentarios', 'pedidos', 'productos', 'usuarios'] as $table) {
            $galy->dropIfExists($table);
        }

        $galy->create('usuarios', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->string('email');
            $table->string('password')->nullable();
            $table->string('telefono')->nullable();
            $table->text('direccion')->nullable();
            $table->boolean('es_admin')->default(false);
            $table->integer('puntos')->default(0);
            $table->string('imagen_url')->nullable();
            $table->boolean('estado')->default(true);
            $table->string('fcm_token')->nullable();
            $table->timestamp('fecha_registro')->nullable();
            $table->timestamps();
        });

        $galy->create('productos', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->integer('stock')->default(0);
            $table->string('imagen_url')->nullable();
            $table->decimal('precio_normal', 10, 2)->default(0);
            $table->decimal('precio_compra', 10, 2)->default(0);
            $table->timestamps();
        });

        $galy->create('pedidos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->decimal('total', 10, 2)->default(0);
            $table->string('estado')->default('PAGADO');
            $table->string('estado_orden')->default('En proceso');
            $table->string('metodo_pago')->default('YAPE');
            $table->string('distrito')->nullable();
            $table->timestamps();
        });

        $galy->create('pedido_productos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->integer('cantidad')->default(1);
        });

        $galy->create('comentarios', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('producto_id')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->text('comentario')->nullable();
            $table->integer('estrellas')->default(5);
            $table->timestamp('fecha')->nullable();
        });

        // La emite el backend del panel de GALY sobre la misma base; aquí se
        // recrea para poder comprobar que el rango cierra sesiones.
        if (! Schema::connection('galy')->hasTable('personal_access_tokens')) {
            $galy->create('personal_access_tokens', function (Blueprint $table): void {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }

    private function actingAsAppAdmin(): User
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

    private function seedAppData(): void
    {
        $galy = DB::connection('galy');

        $galy->table('usuarios')->insert([
            'id' => 7,
            'nombre' => 'Cliente App',
            'email' => 'cliente@example.com',
            'telefono' => '999111222',
            'direccion' => 'Av. Siempre Viva 123',
            'es_admin' => 0,
            'puntos' => 25,
            'estado' => 1,
            'fcm_token' => 'token-1',
            'fecha_registro' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $galy->table('productos')->insert([
            'id' => 3,
            'nombre' => 'Laptop GALY',
            'stock' => 2,
            'precio_normal' => 2000,
            'precio_compra' => 1500,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $galy->table('pedidos')->insert([
            'id' => 11,
            'usuario_id' => 7,
            'total' => 250,
            'estado' => 'PAGADO',
            'estado_orden' => 'En proceso',
            'metodo_pago' => 'YAPE',
            'distrito' => 'Lima',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $galy->table('pedido_productos')->insert([
            'producto_id' => 3,
            'cantidad' => 4,
        ]);

        $galy->table('comentarios')->insert([
            'id' => 5,
            'producto_id' => 3,
            'usuario_id' => 7,
            'comentario' => 'Excelente producto',
            'estrellas' => 5,
            'fecha' => now(),
        ]);
    }

    public function test_app_galy_summary_renders_sales_clients_and_stock(): void
    {
        $this->actingAsAppAdmin();
        $this->seedAppData();

        $this->get(route('admin.app-galy.index'))
            ->assertOk()
            ->assertSee('App GALY')
            ->assertSee('Resumen de la aplicación')
            ->assertSee('S/ 250.00')
            ->assertSee('Laptop GALY');
    }

    public function test_app_galy_customer_list_and_detail_use_the_shared_tables(): void
    {
        $this->actingAsAppAdmin();
        $this->seedAppData();

        $this->get(route('admin.app-galy.clientes'))
            ->assertOk()
            ->assertSee('Cliente App')
            ->assertSee('cliente@example.com');

        $this->get(route('admin.app-galy.cliente', 7))
            ->assertOk()
            ->assertSee('Cliente #7')
            ->assertSee('S/ 250.00');
    }

    public function test_app_galy_customer_can_be_updated_from_the_web_panel(): void
    {
        $this->actingAsAppAdmin();
        $this->seedAppData();

        $this->put(route('admin.app-galy.clientes.update', 7), [
            'nombre' => 'Cliente Actualizado',
            'email' => 'cliente@example.com',
            'telefono' => '988777666',
            'direccion' => 'Nueva dirección',
            'puntos' => 40,
            'estado' => 1,
        ])->assertRedirect(route('admin.app-galy.cliente', 7));

        $this->assertDatabaseHas('usuarios', [
            'id' => 7,
            'nombre' => 'Cliente Actualizado',
            'puntos' => 40,
        ], 'galy');
    }

    public function test_app_galy_notification_is_sent_through_the_push_endpoint(): void
    {
        $this->actingAsAppAdmin();
        $this->seedAppData();
        Config::set('galy.api_url', 'http://localhost/galy_api');
        Config::set('galy.api_key', 'secret-key');
        Http::fake([
            'http://localhost/galy_api/send_push.php' => Http::response([
                'success' => true,
                'message' => 'Anuncio enviado correctamente a 1 dispositivos.',
            ]),
        ]);

        $this->post(route('admin.app-galy.notificaciones.enviar'), [
            'titulo' => 'Oferta especial',
            'mensaje' => 'Revisa los productos en oferta.',
        ])->assertRedirect()
            ->assertSessionHas('mensaje', 'Anuncio enviado correctamente a 1 dispositivos.');

        Http::assertSent(fn ($request) => $request->url() === 'http://localhost/galy_api/send_push.php'
            && $request['titulo'] === 'Oferta especial'
            && $request->hasHeader('X-Galy-Key', 'secret-key'));
    }

    public function test_app_galy_comment_can_be_deleted(): void
    {
        $this->actingAsAppAdmin();
        $this->seedAppData();

        $this->delete(route('admin.app-galy.comentarios.eliminar', 5))
            ->assertRedirect()
            ->assertSessionHas('mensaje');

        $this->assertDatabaseMissing('comentarios', ['id' => 5], 'galy');
    }

    public function test_app_galy_comment_text_can_be_edited_from_the_web(): void
    {
        $this->actingAsAppAdmin();
        $this->seedAppData();

        $this->put(route('admin.app-galy.comentarios.actualizar', 5), [
            'comentario' => 'Buen producto, pero llegó tarde.',
        ])->assertRedirect()->assertSessionHas('mensaje');

        $this->assertDatabaseHas('comentarios', [
            'id' => 5,
            'comentario' => 'Buen producto, pero llegó tarde.',
        ], 'galy');
    }

    public function test_app_galy_orders_link_to_the_order_detail_where_status_is_changed(): void
    {
        $admin = $this->actingAsAppAdmin();
        $this->seedAppData();

        $this->get(route('admin.app-galy.pedidos'))
            ->assertOk()
            ->assertDontSee('Gestionar');

        $admin->roles->first()
            ->givePermissionTo(Permission::firstOrCreate([
                'name' => 'Listado de Pedidos',
                'guard_name' => 'web',
            ]));

        $this->get(route('admin.app-galy.pedidos'))
            ->assertOk()
            ->assertSee('Gestionar')
            ->assertSee(route('admin.pedidos.create', 11), false);
    }

    public function test_app_galy_summary_shows_the_app_admin_functions_and_a_sales_series(): void
    {
        $this->actingAsAppAdmin();
        $this->seedAppData();

        $this->get(route('admin.app-galy.index'))
            ->assertOk()
            ->assertSee('Funciones del administrador de la app')
            ->assertSee('Administradores GALY')
            ->assertSee('Dispositivos push')
            ->assertSee('Ventas de los últimos 7 días');
    }

    public function test_app_galy_orders_list_reads_the_shared_pedidos_table(): void
    {
        $this->actingAsAppAdmin();
        $this->seedAppData();

        $this->get(route('admin.app-galy.pedidos'))
            ->assertOk()
            ->assertSee('Pedidos de la aplicación')
            ->assertSee('#11')
            ->assertSee('Cliente App')
            ->assertSee('S/ 250.00');

        $this->get(route('admin.app-galy.pedidos', ['estado' => 'CANCELADO']))
            ->assertOk()
            ->assertDontSee('Cliente App');
    }

    public function test_app_galy_admin_account_can_be_created_from_the_web(): void
    {
        $this->actingAsAppAdmin();

        $this->post(route('admin.app-galy.administradores.crear'), [
            'nombre' => 'Nueva Admin',
            'email' => 'Nueva.Admin@Example.com',
            'password' => 'contrasena-super-larga',
            'password_confirmation' => 'contrasena-super-larga',
        ])->assertRedirect();

        $cuenta = DB::connection('galy')->table('usuarios')->where('email', 'nueva.admin@example.com')->first();

        $this->assertNotNull($cuenta);
        $this->assertSame(1, (int) $cuenta->es_admin);
        $this->assertTrue(Hash::check('contrasena-super-larga', (string) $cuenta->password));
    }

    public function test_the_last_app_admin_cannot_lose_the_admin_rank(): void
    {
        $this->actingAsAppAdmin();
        $this->seedAppData();
        DB::connection('galy')->table('usuarios')->where('id', 7)->update(['es_admin' => 1]);

        $this->put(route('admin.app-galy.administradores.alternar', 7), ['es_admin' => 0])
            ->assertSessionHasErrors('administrador');

        $this->assertSame(1, (int) DB::connection('galy')->table('usuarios')->where('id', 7)->value('es_admin'));
    }

    public function test_revoking_an_admin_closes_its_sessions_and_syncs_the_web_account(): void
    {
        $admin = $this->actingAsAppAdmin();
        $this->seedAppData();

        DB::connection('galy')->table('usuarios')->where('id', 7)->update(['es_admin' => 1]);

        DB::connection('galy')->table('usuarios')->insert([
            'id' => 8,
            'nombre' => 'Otro Admin',
            'email' => 'otro@example.com',
            'es_admin' => 1,
            'estado' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $vinculada = User::factory()->create(['email' => 'otro@example.com']);
        $vinculada->galy_user_id = 8;
        $vinculada->galy_es_admin = true;
        $vinculada->save();

        DB::connection('galy')->table('personal_access_tokens')->insert([
            'tokenable_type' => 'App\\Models\\User',
            'tokenable_id' => 8,
            'name' => 'galy-web-sso',
            'token' => 'abcdef1234567890',
            'abilities' => json_encode(['user', 'admin']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->put(route('admin.app-galy.administradores.alternar', 8), ['es_admin' => 0])
            ->assertSessionHas('mensaje');

        $this->assertSame(0, (int) DB::connection('galy')->table('usuarios')->where('id', 8)->value('es_admin'));
        $this->assertSame(0, DB::connection('galy')->table('personal_access_tokens')->where('tokenable_id', 8)->count());
        $this->assertFalse((bool) $vinculada->fresh()->galy_es_admin);
    }

    public function test_app_password_can_be_reset_from_the_web_and_forces_a_new_login(): void
    {
        $this->actingAsAppAdmin();
        $this->seedAppData();

        DB::connection('galy')->table('personal_access_tokens')->insert([
            'tokenable_type' => 'App\\Models\\User',
            'tokenable_id' => 7,
            'name' => 'galy-mobile-and-admin',
            'token' => 'fedcba0987654321',
            'abilities' => json_encode(['user']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->put(route('admin.app-galy.clientes.password', 7), [
            'password' => 'nueva-clave-segura',
            'password_confirmation' => 'nueva-clave-segura',
        ])->assertSessionHas('mensaje');

        $hash = DB::connection('galy')->table('usuarios')->where('id', 7)->value('password');

        $this->assertTrue(Hash::check('nueva-clave-segura', (string) $hash));
        $this->assertSame(0, DB::connection('galy')->table('personal_access_tokens')->where('tokenable_id', 7)->count());
    }

    public function test_push_devices_are_listed_and_can_be_unlinked(): void
    {
        $this->actingAsAppAdmin();
        $this->seedAppData();

        $this->get(route('admin.app-galy.dispositivos'))
            ->assertOk()
            ->assertSee('Dispositivos push')
            ->assertSee('Cliente App');

        $this->delete(route('admin.app-galy.dispositivos.revocar', 7))
            ->assertSessionHas('mensaje');

        $this->assertNull(DB::connection('galy')->table('usuarios')->where('id', 7)->value('fcm_token'));
    }

    public function test_the_app_panel_only_opens_with_the_linked_account_of_the_current_admin(): void
    {
        $admin = $this->actingAsAppAdmin();
        $this->seedAppData();

        $rol = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'ADMINISTRADOR', 'guard_name' => 'web']);
        $admin->syncRoles([$rol]);
        $admin->galy_user_id = 7;
        $admin->galy_es_admin = true;
        $admin->save();

        DB::connection('galy')->table('usuarios')->where('id', 7)->update([
            'es_admin' => 1,
            'email' => 'propia@example.com',
        ]);

        Config::set('galy.admin_api_url', 'http://localhost/galy-admin/api/v1');
        Config::set('galy.public_url', 'http://localhost/galy-admin');
        Config::set('galy.sso_secret', 'secret-de-prueba-para-firmas-sso');

        Http::fake([
            'http://localhost/galy-admin/api/v1/auth/web-sso' => Http::response(['success' => true, 'token' => 'token-panuel']),
        ]);

        $this->get(route('admin.galy-panel', ['usuario' => 9]))
            ->assertRedirect('http://localhost/galy-admin/login?sso_token=token-panuel');

        Http::assertSent(function ($request): bool {
            return $request['user_id'] === 7
                && $request['email'] === 'propia@example.com'
                && is_string($request['signature'] ?? null);
        });
    }

    public function test_a_client_without_admin_rank_cannot_open_the_app_panel(): void
    {
        $admin = $this->actingAsAppAdmin();

        $rol = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'ADMINISTRADOR', 'guard_name' => 'web']);
        $admin->syncRoles([$rol]);
        $admin->galy_user_id = 7;
        $admin->galy_es_admin = false;
        $admin->save();

        Http::fake();

        $this->get(route('admin.galy-panel'))->assertForbidden();
        Http::assertNothingSent();
    }
}
