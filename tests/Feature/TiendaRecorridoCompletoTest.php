<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Concerns\CreatesOrderPaymentTables;
use Tests\TestCase;

/**
 * Recorre la tienda como la recorre un cliente: si una vista se rompe,
 * esta prueba lo avisa antes de que él lo vea en el navegador.
 */
class TiendaRecorridoCompletoTest extends TestCase
{
    use CreatesOrderPaymentTables;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // `pedidos`, `producto_imagenes` y `reservas_productos` no nacen de las migraciones.
        $this->crearTablasDePagos();

        // Fuera de pruebas, `ProductoFavorito` vive en la conexión GALY; en pruebas esa
        // conexión se resuelve como la predeterminada (ver UsesGalyConnection).
        Schema::create('favoritos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->unsignedBigInteger('producto_id');
            $table->timestamp('fecha_agregado')->nullable();
            $table->timestamps();
        });
    }

    private function prepararContenido(): Producto
    {
        DB::table('ajustes')->insert([
            'nombre' => 'Tectron Galy',
            'descripcion' => 'Tienda de tecnología',
            'sucursal' => 'Principal',
            'direccion' => 'Av. Siempre Verde 123',
            'telefono' => '987654321',
            'logo' => 'logo.png',
            'imagen_login' => 'login.png',
            'email' => 'ventas@tectrongaly.com',
            'divisa' => 'PEN',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // La migración de `nosotros` ya deja una fila semilla; /nosotros responde 404
        // si está inactiva, así que se enciende en lugar de crear una segunda.
        DB::table('nosotros')->update(['activo' => true]);

        return Producto::factory()->create(['stock' => 10]);
    }

    public static function urlsPublicas(): array
    {
        return [
            'portada' => ['/'],
            'catálogo' => ['/productos'],
            'ofertas' => ['/ofertas'],
            'marcas' => ['/marcas'],
            'nosotros' => ['/nosotros'],
            'asesores' => ['/asesores'],
            'contacto' => ['/contacto'],
            'búsqueda' => ['/buscar?q=laptop'],
            'buscar sugerencias' => ['/api/buscar-sugerencias?q=laptop'],
            'entrar' => ['/login'],
            'registro' => ['/register'],
            'recuperar contraseña' => ['/password/reset'],
            'términos' => ['/terminos-y-condiciones'],
            'privacidad' => ['/politica-de-privacidad'],
            'cookies' => ['/politica-de-cookies'],
            'devoluciones' => ['/cambios-y-devoluciones'],
        ];
    }

    /**
     * Página pública que cualquier visitante abre sin tener cuenta.
     */
    #[DataProvider('urlsPublicas')]
    public function test_a_public_page_renders_without_errors(string $url): void
    {
        $this->prepararContenido();

        $this->get($url)->assertSuccessful();
    }

    public function test_a_product_and_a_category_page_render(): void
    {
        $producto = $this->prepararContenido();

        $this->get('/producto/'.$producto->id)->assertSuccessful();
        $this->get('/categoria/'.$producto->categoria->slug)->assertSuccessful();
    }

    public function test_every_page_a_customer_uses_renders(): void
    {
        $this->prepararContenido();

        $usuario = User::factory()->create();
        $usuario->forceFill(['galy_user_id' => $usuario->id])->save();

        // /home queda fuera: es el panel de administración y exige el permiso
        // «Ver dashboard del Admin», así que un 403 ahí es lo correcto.
        // /reservas tampoco va aquí: su listado lee la conexión GALY, que en pruebas es
        // una base aparte; esa ruta la cubre ReservaProductoServiceTest.
        foreach (['/dashboard', '/carrito', '/favoritos', '/ajustes'] as $url) {
            $respuesta = $this->actingAs($usuario)->get($url);

            $this->assertTrue($respuesta->isSuccessful(), $url.' devolvió '.$respuesta->status());
        }
    }

    public function test_a_guest_is_sent_to_login_instead_of_seeing_an_error(): void
    {
        $this->prepararContenido();

        foreach (['/home', '/dashboard', '/carrito', '/favoritos', '/reservas', '/ajustes'] as $url) {
            $this->get($url)->assertRedirect(route('web.login'));
        }
    }
}
