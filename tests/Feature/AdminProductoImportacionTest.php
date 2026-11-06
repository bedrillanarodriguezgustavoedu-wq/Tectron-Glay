<?php

namespace Tests\Feature;

use App\Models\Ajuste;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Carga masiva de inventario desde el panel: pegar la lista de Excel, ver la
 * previsualización y crear los productos sin duplicar los que ya existen.
 */
class AdminProductoImportacionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $rol = Role::firstOrCreate(['name' => 'SUPER ADMIN', 'guard_name' => 'web']);
        foreach (['Listado de Productos', 'Crear Producto', 'Guardar Producto'] as $permiso) {
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

        Categoria::firstOrCreate(['nombre' => 'Mouses'], ['slug' => 'mouses-import']);
        Categoria::firstOrCreate(['nombre' => 'TINTA DE IMPRESORA'], ['slug' => 'tinta-import']);
        Marca::firstOrCreate(['nombre' => 'GENIUS'], ['slug' => 'genius-import', 'activo' => true]);
    }

    public function test_la_pantalla_de_importacion_carga_para_el_admin(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/productos/importar')
            ->assertOk()
            ->assertSee('Importar productos')
            ->assertSee('Previsualizar');
    }

    public function test_sin_permiso_no_se_puede_entrar(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/admin/productos/importar')
            ->assertForbidden();
    }

    public function test_la_previsualizacion_no_escribe_nada(): void
    {
        $respuesta = $this->actingAs($this->admin)->post('/admin/productos/importar', [
            'accion' => 'previsualizar',
            'filas' => "DESCRIPCIÓN\tMODELO\tCANTIDAD\tPRECIO COMPRA\tPRECIO VENTA\tCATEGORÍA\n"
                ."1\tMOUSE GENIUS\tDX 101\t6\t28.00\t45.00\tMouses\n"
                ."2\tTINTA CANON 11 MAGENTA\tGI-11\t1\t55.00\t90.00\t\n",
        ]);

        $respuesta->assertOk()
            ->assertSee('Previsualización (2 filas)')
            ->assertSee('MOUSE GENIUS DX 101')
            ->assertSee('Se creará');

        $this->assertSame(0, Producto::count());
    }

    public function test_importa_las_filas_detectando_categoria_marca_y_codigos_unicos(): void
    {
        $respuesta = $this->actingAs($this->admin)->post('/admin/productos/importar', [
            'accion' => 'importar',
            'filas' => "1\tMOUSE GENIUS\tDX 101\t6\t28.00\t45.00\t\n"
                ."2\tMOUSE GENIUS\tDX 110\t2\t30.00\t48.00\t\n"
                ."3\tTINTA CANON 11 MAGENTA\t\t1\t55.00\t90.00\tTINTA DE IMPRESORA\n",
        ]);

        $respuesta->assertRedirect('/admin/productos')
            ->assertSessionHas('mensaje', '3 productos creados, 0 actualizados y 0 omitidos.');

        $mouse = Producto::where('codigo', 'like', 'TEC-IMP-MOUSE-GENIUS-DX-101%')->first();
        $this->assertNotNull($mouse);
        $this->assertSame('MOUSE GENIUS DX 101', $mouse->nombre);
        $this->assertSame(6, (int) $mouse->stock);
        $this->assertSame(28.0, (float) $mouse->precio_compra);
        $this->assertSame(45.0, (float) $mouse->precio_venta);
        $this->assertSame('Mouses', $mouse->categoria->nombre);
        $this->assertSame('GENIUS', $mouse->marca->nombre);

        $tinta = Producto::where('codigo', 'like', 'TEC-IMP-TINTA-CANON-11-MAGENTA%')->first();
        $this->assertNotNull($tinta);
        $this->assertSame('TINTA DE IMPRESORA', $tinta->categoria->nombre);

        $this->assertSame(3, Producto::count());
    }

    public function test_no_duplica_si_el_producto_ya_existe_y_solo_actualiza_cuando_se_pide(): void
    {
        $codigo = 'ACC-001';
        $producto = Producto::create([
            'categoria_id' => Categoria::where('nombre', 'Mouses')->value('id'),
            'nombre' => 'Mouse Genius DX-101',
            'codigo' => $codigo,
            'descripcion_corta' => 'Mouse',
            'descripcion_larga' => 'Mouse',
            'precio_compra' => 20,
            'precio_venta' => 30,
            'stock' => 1,
        ]);

        $sinActualizar = $this->actingAs($this->admin)->post('/admin/productos/importar', [
            'accion' => 'importar',
            'filas' => "Mouse Genius DX-101\t\t9\t25.00\t40.00\tMouses\n",
        ]);

        $sinActualizar->assertRedirect('/admin/productos')
            ->assertSessionHas('mensaje', '0 productos creados, 0 actualizados y 1 omitidos.');

        $this->assertSame(1, Producto::count());
        $this->assertSame(1, (int) $producto->fresh()->stock);

        $conActualizar = $this->actingAs($this->admin)->post('/admin/productos/importar', [
            'accion' => 'importar',
            'actualizar' => '1',
            'filas' => "Mouse Genius DX-101\t\t9\t25.00\t40.00\tMouses\n",
        ]);

        $conActualizar->assertSessionHas('mensaje', '0 productos creados, 1 actualizados y 0 omitidos.');

        $producto->refresh();
        $this->assertSame(1, Producto::count());
        $this->assertSame(9, (int) $producto->stock);
        $this->assertSame(40.0, (float) $producto->precio_venta);
    }

    public function test_omite_filas_sin_precio_de_venta_y_las_reporta(): void
    {
        $respuesta = $this->actingAs($this->admin)->post('/admin/productos/importar', [
            'accion' => 'importar',
            'filas' => "MOUSE SEISA\tDN-N613\t3\t\t\tMouses\n",
        ]);

        $respuesta->assertRedirect('/admin/productos')
            ->assertSessionHas('mensaje', '0 productos creados, 0 actualizados y 1 omitidos.')
            ->assertSessionHas('importacion_motivos');

        $this->assertSame(0, Producto::count());
    }
}
