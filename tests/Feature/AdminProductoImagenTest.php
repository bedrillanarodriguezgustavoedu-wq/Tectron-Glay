<?php

namespace Tests\Feature;

use App\Models\Ajuste;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\ProductoImagen;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Cambio de imagen desde el panel: la foto marcada como principal debe ser la
 * que se ve en la tienda y el admin debe recibir una notificación visible.
 */
class AdminProductoImagenTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private string $carpetaImagenes;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('producto_imagenes')) {
            Schema::create('producto_imagenes', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('producto_id');
                $table->string('imagen');
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('productos', 'imagen_url')) {
            Schema::table('productos', function (Blueprint $table): void {
                $table->string('imagen_url')->nullable();
            });
        }

        $this->carpetaImagenes = storage_path('app/test-product-images');
        config()->set('galy.images_path', $this->carpetaImagenes);

        $rol = Role::firstOrCreate(['name' => 'SUPER ADMIN', 'guard_name' => 'web']);
        foreach ([
            'Listado de Productos',
            'Gestionar imágenes de Producto',
            'Subir imágenes de Producto',
            'Eliminar imágenes de Producto',
        ] as $permiso) {
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

    protected function tearDown(): void
    {
        File::deleteDirectory($this->carpetaImagenes);

        parent::tearDown();
    }

    private function producto(string $nombre = 'Monitor de prueba', string $codigo = 'PRU-IMG'): Producto
    {
        $categoria = Categoria::firstOrCreate(['nombre' => 'Monitores'], ['slug' => 'monitores-imagen']);

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

    private function imagen(Producto $producto, string $archivo): ProductoImagen
    {
        return ProductoImagen::create([
            'producto_id' => $producto->id,
            'imagen' => $archivo,
        ]);
    }

    /**
     * Archivo real mínimo (PNG de 1x1) porque en este equipo no está GD y
     * `UploadedFile::fake()->image()` no puede generar la imagen.
     */
    private function pngDePrueba(string $nombre = 'subida.png'): UploadedFile
    {
        File::ensureDirectoryExists($this->carpetaImagenes);
        $ruta = $this->carpetaImagenes.DIRECTORY_SEPARATOR.$nombre;
        File::put($ruta, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));

        return new UploadedFile($ruta, $nombre, 'image/png', null, true);
    }

    public function test_la_ficha_usa_la_imagen_marcada_como_principal(): void
    {
        $producto = $this->producto();
        $this->imagen($producto, 'foto-vieja.jpg');
        $this->imagen($producto, 'foto-nueva.webp');

        // resolveUrl solo devuelve el archivo si existe en la carpeta de GALY.
        File::ensureDirectoryExists($this->carpetaImagenes);
        File::put($this->carpetaImagenes.DIRECTORY_SEPARATOR.'foto-vieja.jpg', 'x');
        File::put($this->carpetaImagenes.DIRECTORY_SEPARATOR.'foto-nueva.webp', 'x');

        $producto->setAttribute('imagen_url', 'foto-nueva.webp');
        $producto->save();

        $this->assertStringContainsString(
            'foto-nueva.webp',
            $producto->fresh('imagenes')->imagen_principal_url
        );
        $this->assertStringNotContainsString(
            'foto-vieja.jpg',
            $producto->fresh('imagenes')->imagen_principal_url
        );
    }

    public function test_subir_una_imagen_la_marca_como_principal_y_avisa(): void
    {
        $producto = $this->producto();
        $this->imagen($producto, 'foto-vieja.jpg');

        $respuesta = $this->actingAs($this->admin)->post(
            '/admin/producto/'.$producto->id.'/upload_imagen',
            ['imagen' => $this->pngDePrueba()]
        );

        $respuesta->assertRedirect(route('admin.productos.imagenes', $producto->id));
        $respuesta->assertSessionHas('icono', 'success');
        $respuesta->assertSessionHas('mensaje', 'Imagen subida correctamente. Ahora es la imagen principal del producto.');

        $producto->refresh();
        $this->assertNotNull($producto->imagen_url);
        $this->assertStringContainsString('tectron-product-'.$producto->id.'-', $producto->imagen_url);
        $this->assertFileExists($this->carpetaImagenes.DIRECTORY_SEPARATOR.$producto->imagen_url);
        $this->assertStringContainsString(
            $producto->imagen_url,
            $producto->imagen_principal_url
        );
        $this->assertDatabaseHas('producto_imagenes', [
            'producto_id' => $producto->id,
            'imagen' => $producto->imagen_url,
        ]);
    }

    public function test_se_puede_cambiar_la_principal_y_la_tienda_lo_refleja(): void
    {
        $producto = $this->producto();
        $vieja = $this->imagen($producto, 'foto-vieja.jpg');
        $nueva = $this->imagen($producto, 'foto-nueva.webp');
        $producto->setAttribute('imagen_url', 'foto-vieja.jpg');
        $producto->save();

        File::ensureDirectoryExists($this->carpetaImagenes);
        File::put($this->carpetaImagenes.DIRECTORY_SEPARATOR.'foto-vieja.jpg', 'x');
        File::put($this->carpetaImagenes.DIRECTORY_SEPARATOR.'foto-nueva.webp', 'x');

        $respuesta = $this->actingAs($this->admin)
            ->put('/admin/producto/imagen/'.$nueva->id.'/principal');

        $respuesta->assertRedirect(route('admin.productos.imagenes', $producto->id));
        $respuesta->assertSessionHas('icono', 'success');
        $respuesta->assertSessionHas('mensaje', 'Imagen principal actualizada. Ya se muestra en la tienda.');

        $this->assertSame('foto-nueva.webp', $producto->fresh()->imagen_url);
        $this->assertStringContainsString('foto-nueva.webp', $producto->fresh('imagenes')->imagen_principal_url);
        $this->assertStringNotContainsString('foto-vieja.jpg', $producto->fresh('imagenes')->imagen_principal_url);
        $this->assertSame($nueva->id, $producto->fresh()->imagenes->firstWhere('imagen', 'foto-nueva.webp')?->id);
        $this->assertSame($vieja->id, $producto->fresh()->imagenes->firstWhere('imagen', 'foto-vieja.jpg')?->id);
    }

    public function test_la_pantalla_muestra_la_notificacion_de_exito(): void
    {
        $producto = $this->producto();
        $this->imagen($producto, 'foto.jpg');

        $respuesta = $this->actingAs($this->admin)
            ->withSession([
                'mensaje' => 'Imagen subida correctamente. Ahora es la imagen principal del producto.',
                'icono' => 'success',
            ])
            ->get('/admin/producto/'.$producto->id.'/imagenes');

        $respuesta->assertOk()
            ->assertSee('flash-mensaje', false)
            ->assertSee('Imagen subida correctamente. Ahora es la imagen principal del producto.')
            ->assertSee('sweetalert2', false);
    }

    public function test_una_subida_sin_archivo_muestra_notificacion_de_error(): void
    {
        $producto = $this->producto();

        $respuesta = $this->actingAs($this->admin)
            ->from('/admin/producto/'.$producto->id.'/imagenes')
            ->followingRedirects()
            ->post('/admin/producto/'.$producto->id.'/upload_imagen', []);

        $respuesta->assertOk()
            ->assertSee('flash-errores', false)
            ->assertSee('No se pudo completar la acción')
            ->assertSee('Selecciona una imagen antes de guardar.');
    }

    public function test_la_imagen_principal_se_ve_marcada_en_la_galeria_del_admin(): void
    {
        $producto = $this->producto();
        $otra = $this->imagen($producto, 'foto-vieja.jpg');
        $nueva = $this->imagen($producto, 'foto-nueva.webp');
        $producto->setAttribute('imagen_url', 'foto-nueva.webp');
        $producto->save();

        $this->actingAs($this->admin)
            ->get('/admin/producto/'.$producto->id.'/imagenes')
            ->assertOk()
            ->assertSee('Principal')
            ->assertSee(route('admin.productos.imagen_principal', $otra->id))
            ->assertDontSee(route('admin.productos.imagen_principal', $nueva->id));
    }
}
