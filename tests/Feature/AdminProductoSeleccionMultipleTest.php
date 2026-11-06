<?php

namespace Tests\Feature;

use App\Models\Ajuste;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Selección múltiple del listado de productos: qué se puede marcar, qué hace
 * falta para borrar y, sobre todo, que borrar no sea definitivo. El catálogo se
 * comparte con la app Android y no tiene copia detrás.
 */
class AdminProductoSeleccionMultipleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // La tabla de la galería la crea el importado Tectron, no una migración.
        if (! Schema::hasTable('producto_imagenes')) {
            Schema::create('producto_imagenes', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('producto_id');
                $table->string('imagen');
                $table->timestamps();
            });
        }

        $rol = Role::firstOrCreate(['name' => 'SUPER ADMIN', 'guard_name' => 'web']);

        foreach (['Listado de Productos', 'Eliminar Producto', 'Editar Producto'] as $permiso) {
            $rol->givePermissionTo(Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']));
        }

        $this->admin = User::factory()->create();
        $this->admin->assignRole($rol);

        Ajuste::create([
            'nombre' => 'Tectron Galy',
            'descripcion' => 'Tienda de prueba',
            'sucursal' => 'Matriz',
            'direccion' => 'Av. Siempre Viva 123',
            'telefono' => '0998765432',
            'email' => 'prueba@galy.pe',
            'divisa' => 'PEN',
            'logo' => 'logos/prueba.jpg',
            'imagen_login' => 'imagenes_login/prueba.jpg',
        ]);
    }

    private function producto(string $nombre, string $codigo): Producto
    {
        $categoria = Categoria::firstOrCreate(['nombre' => 'Monitores'], ['slug' => 'monitores-prueba']);

        return Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => $nombre,
            'codigo' => $codigo,
            'descripcion_corta' => 'Descripción corta de prueba',
            'descripcion_larga' => 'Descripción larga de prueba',
            'precio_compra' => 100,
            'precio_venta' => 150,
            'stock' => 5,
        ]);
    }

    public function test_el_listado_trae_casillas_y_la_barra_de_acciones(): void
    {
        $monitor = $this->producto('Monitor de prueba', 'PRU-1');

        $respuesta = $this->actingAs($this->admin)->get('/admin/productos');

        $respuesta->assertOk()
            ->assertSee('masivoForm', false)
            ->assertSee('marcarPagina', false)
            ->assertSee('Eliminar seleccionados')
            ->assertSee('Papelera', false)
            ->assertSee('name="ids[]" value="'.$monitor->id.'"', false);
    }

    public function test_sin_marcar_nada_no_toca_los_productos(): void
    {
        $producto = $this->producto('Monitor sin marcar', 'PRU-2');

        $respuesta = $this->actingAs($this->admin)->post('/admin/productos/masivo', [
            'accion' => 'eliminar',
            'alcance' => 'seleccion',
            'ids' => [],
            'confirmar' => 'ELIMINAR',
        ]);

        $respuesta->assertSessionHas('errors');
        $this->assertNull($producto->fresh()?->deleted_at);
    }

    public function test_borrar_pide_la_palabra_eliminar(): void
    {
        $producto = $this->producto('Monitor sin confirmar', 'PRU-3');

        $respuesta = $this->actingAs($this->admin)->post('/admin/productos/masivo', [
            'accion' => 'eliminar',
            'alcance' => 'seleccion',
            'ids' => [$producto->id],
            'confirmar' => 'si',
        ]);

        $respuesta->assertSessionHasErrors('confirmar');
        $this->assertNull($producto->fresh()?->deleted_at);
    }

    public function test_eliminar_deja_el_producto_vivo_en_la_papelera(): void
    {
        $producto = $this->producto('Monitor a la papelera', 'PRU-4');
        DB::table('producto_imagenes')->insert([
            'producto_id' => $producto->id,
            'imagen' => 'productos/default.webp',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->admin)->post('/admin/productos/masivo', [
            'accion' => 'eliminar',
            'alcance' => 'seleccion',
            'ids' => [$producto->id],
            'confirmar' => 'ELIMINAR',
        ])->assertRedirect();

        $this->assertNotNull(DB::table('productos')->where('id', $producto->id)->value('deleted_at'));
        $this->assertSame(1, DB::table('producto_imagenes')->where('producto_id', $producto->id)->count());
        $this->actingAs($this->admin)->get('/admin/productos')->assertDontSee('Monitor a la papelera');
        $this->actingAs($this->admin)->get('/admin/productos?eliminados=1')
            ->assertSee('Monitor a la papelera')
            ->assertSee('Devolver');
    }

    public function test_devolver_un_producto_saca_de_la_papelera(): void
    {
        $producto = $this->producto('Monitor recuperado', 'PRU-5');

        DB::table('producto_imagenes')->insert([
            'producto_id' => $producto->id,
            'imagen' => 'productos/default.webp',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->admin)->post('/admin/productos/masivo', [
            'accion' => 'eliminar',
            'alcance' => 'seleccion',
            'ids' => [$producto->id],
            'confirmar' => 'ELIMINAR',
        ]);

        $this->actingAs($this->admin)->post('/admin/productos/masivo', [
            'accion' => 'restaurar',
            'alcance' => 'seleccion',
            'ids' => [$producto->id],
            'eliminados' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('producto_imagenes', ['producto_id' => $producto->id]);
        $this->assertNotNull(Producto::find($producto->id));

        $this->assertNotNull(
            DB::table('productos')->where('id', $producto->id)->whereNull('deleted_at')->value('id')
        );
    }

    public function test_los_del_listado_significa_todos_los_que_filtra_la_busqueda(): void
    {
        $monitor1 = $this->producto('Monitor uno', 'PRU-6');
        $monitor2 = $this->producto('Monitor dos', 'PRU-7');
        $laptop = $this->producto('Laptop que sigue', 'PRU-8');

        $this->actingAs($this->admin)->post('/admin/productos/masivo', [
            'accion' => 'eliminar',
            'alcance' => 'filtro',
            'buscar' => 'Monitor',
            'confirmar' => 'ELIMINAR',
        ])->assertRedirect();

        $this->assertSoftDeleted('productos', ['id' => $monitor1->id]);
        $this->assertSoftDeleted('productos', ['id' => $monitor2->id]);
        $this->assertNotNull(Producto::find($laptop->id));
    }

    public function test_el_borrado_suave_suelta_el_carrito(): void
    {
        $producto = $this->producto('Monitor en el carrito', 'PRU-9');

        DB::table('carrito')->insert([
            'usuario_id' => $this->admin->id,
            'producto_id' => $producto->id,
            'cantidad' => 1,
        ]);

        $this->actingAs($this->admin)->post('/admin/productos/masivo', [
            'accion' => 'eliminar',
            'alcance' => 'seleccion',
            'ids' => [$producto->id],
            'confirmar' => 'ELIMINAR',
        ]);

        $this->assertSame(0, DB::table('carrito')->where('producto_id', $producto->id)->count());
    }

    public function test_sin_el_permiso_no_deja_borrar_nada(): void
    {
        $producto = $this->producto('Monitor protegido', 'PRU-10');
        $visitante = User::factory()->create();

        $this->actingAs($visitante)->post('/admin/productos/masivo', [
            'accion' => 'eliminar',
            'alcance' => 'seleccion',
            'ids' => [$producto->id],
            'confirmar' => 'ELIMINAR',
        ])->assertForbidden();

        $this->assertNotNull(Producto::find($producto->id));
    }
}
