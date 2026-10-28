<?php

namespace Tests\Feature;

use App\Models\Departamento;
use App\Models\Pais;
use App\Models\Producto;
use App\Models\Provincia;
use App\Models\User;
use App\Services\MercadoPagoService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Concerns\CreatesOrderPaymentTables;
use Tests\TestCase;

/**
 * El cupón del carrito web usa las mismas reglas que la app (`coupon_api_common.php`):
 * el código se normaliza, el porcentaje se topea en 90 % y el descuento baja solo de
 * los productos. En la sesión nunca viaja un monto, solo el código, y el pedido se
 * recalcula contra el subtotal real.
 */
class CarritoCuponTest extends TestCase
{
    use CreatesOrderPaymentTables;
    use RefreshDatabase;

    private const SANDBOX_URL = 'https://sandbox.mercadopago.com/checkout/primicia';

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearTablasDePagos();

        Schema::create('cupones', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->integer('descuento_porcentaje');
            $table->date('fecha_expiracion')->nullable();
            $table->boolean('activo')->default(true);
        });
    }

    private function cupon(array $atributos = []): void
    {
        DB::table('cupones')->insert(array_merge([
            'codigo' => 'GALY10',
            'descuento_porcentaje' => 10,
            'fecha_expiracion' => '2030-12-31',
            'activo' => 1,
        ], $atributos));
    }

    /** Cliente con un producto de $precio en el carrito y pagos del canal web listos. */
    private function crearClienteConCarrito(float $precio = 550): User
    {
        $usuario = User::factory()->create([
            'galy_user_id' => 42,
            'google_id' => 'google-42',
            'email_verified_at' => now(),
        ]);

        $producto = Producto::factory()->create(['stock' => 5]);
        $producto->forceFill([
            'precio_venta' => $precio,
            'precio_oferta' => null,
            'en_oferta' => false,
        ])->save();

        DB::connection('galy')->table('productos')->insert([
            'id' => $producto->id,
            'categoria_id' => $producto->categoria_id,
            'stock' => 5,
        ]);

        DB::table('ajustes')->insert([
            'nombre' => 'GALY',
            'descripcion' => 'Tienda',
            'sucursal' => 'Principal',
            'direccion' => 'Dirección',
            'telefono' => '999999999',
            'logo' => 'logo.png',
            'imagen_login' => 'login.png',
            'email' => 'tienda@example.com',
            'divisa' => 'PEN',
            'web_yape_numero' => '987654321',
            'web_yape_titular' => 'Titular Yape',
            'web_yape_qr' => 'pagos/qr/web/yape.png',
            'web_plin_numero' => '912345678',
            'web_plin_titular' => 'Titular Plin',
            'web_plin_qr' => 'pagos/qr/web/plin.png',
            'web_mercadopago_activa' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $peru = Pais::where('nombre', 'Perú')->firstOrFail();
        $departamento = Departamento::where('pais_id', $peru->id)->firstOrFail();
        $provincia = Provincia::where('departamento_id', $departamento->id)->firstOrFail();

        \App\Models\Direccion::create([
            'usuario_id' => $usuario->id,
            'alias' => 'Casa',
            'pais_id' => $peru->id,
            'departamento_id' => $departamento->id,
            'provincia_id' => $provincia->id,
            'direccion_exacta' => 'Calle Falsa 123',
            'es_predeterminada' => true,
        ]);


        DB::table('carrito')->insert([
            'usuario_id' => $usuario->galy_user_id,
            'producto_id' => $producto->id,
            'cantidad' => 1,
        ]);

        return $usuario;
    }

    private function configurarPasarela(): void
    {
        config([
            'services.mercadopago.enabled' => true,
            'services.mercadopago.access_token' => 'TEST-8514936306504285-091415-3733c7f9ab12',
            'services.mercadopago.webhook_secret' => null,
            'services.mercadopago.currency' => 'PEN',
            'services.mercadopago.checkout_ttl_minutes' => 30,
        ]);

        app(MercadoPagoService::class)->registrarVerificacion();

        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref-cupon',
                'init_point' => 'https://www.mercadopago.com/checkout/produccion',
                'sandbox_init_point' => self::SANDBOX_URL,
            ]),
        ]);
    }

    private function datosCheckout(string $metodo): array
    {
        return [
            'name' => 'Cliente GALY',
            'dni' => '12345678',
            'telefono' => '987654321',
            'privacy_consent' => '1',
            'direccion_id' => 1,
            'metodo_pago' => $metodo,
            'modalidad_entrega' => 'recojo',
        ];
    }

    private function aplicarCupon(User $usuario, string $codigo): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($usuario)
            ->from(route('web.carrito.index'))
            ->post(route('web.carrito.cupon'), ['codigo' => $codigo]);
    }

    public function test_el_cliente_ve_el_descuento_del_cupon_en_el_resumen(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->cupon();

        $this->aplicarCupon($usuario, 'galy10')
            ->assertRedirect(route('web.carrito.index'))
            ->assertSessionHas('icono', 'success');

        // En la sesión solo viaja el código normalizado, nunca el monto.
        $this->assertSame('GALY10', session('carrito_cupon'));

        $this->actingAs($usuario)
            ->get(route('web.carrito.index'))
            ->assertSuccessful()
            ->assertSee('GALY10', false)
            ->assertSee('−10%', false)
            ->assertSee('-'.\App\Models\Ajuste::formatear_precio(55), false)
            ->assertDontSee('class="summary-item d-none" id="cupon-container"', false)
            // 550 − 55 (el subtotal pasa del umbral, así que el envío ya era gratis).
            ->assertSee('id="hidden-total" value="495"', false);
    }

    public function test_el_descuento_no_toca_el_costo_del_envio(): void
    {
        $usuario = $this->crearClienteConCarrito(250);
        $this->cupon();

        $this->aplicarCupon($usuario, 'GALY10')->assertSessionHas('icono', 'success');

        // 250 − 25 + 20: el descuento baja el total pero el envío se cobra igual,
        // porque el umbral de envío gratis se mide antes del cupón.
        $this->actingAs($usuario)
            ->get(route('web.carrito.index'))
            ->assertSee('id="hidden-total" value="245"', false)
            // Siguen faltando 50 (no 75): el cupón no acerca ni aleja el envío gratis.
            ->assertSee('Te faltan '.\App\Models\Ajuste::formatear_precio(50), false)
            ->assertDontSee('Te faltan '.\App\Models\Ajuste::formatear_precio(75), false);
    }

    public function test_un_codigo_que_no_existe_no_se_guarda(): void
    {
        $usuario = $this->crearClienteConCarrito();

        $this->aplicarCupon($usuario, 'NOEXISTE')
            ->assertRedirect(route('web.carrito.index'))
            ->assertSessionHas('icono', 'warning');

        $this->assertNull(session('carrito_cupon'));
    }

    public function test_un_codigo_pausado_o_vencido_no_descuenta(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->cupon(['codigo' => 'PAUSADO', 'activo' => 0]);
        $this->cupon(['codigo' => 'VENCIDO', 'fecha_expiracion' => '2020-01-01']);

        $this->aplicarCupon($usuario, 'PAUSADO')->assertSessionHas('icono', 'warning');
        $this->aplicarCupon($usuario, 'VENCIDO')->assertSessionHas('icono', 'warning');

        $this->assertNull(session('carrito_cupon'));
    }

    /**
     * Un cupón que caduca mientras la página sigue abierta deja de aplicar solo con
     * recargar: el código de la sesión se resuelve contra la base en cada visita.
     */
    public function test_un_cupon_que_caduca_deja_de_aplicar_al_recargar(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->cupon(['fecha_expiracion' => now()->addDay()->toDateString()]);

        $this->aplicarCupon($usuario, 'GALY10')->assertSessionHas('icono', 'success');
        $this->assertSame('GALY10', session('carrito_cupon'));

        DB::table('cupones')->update(['fecha_expiracion' => now()->subDay()->toDateString()]);

        $this->actingAs($usuario)
            ->get(route('web.carrito.index'))
            ->assertSee('class="summary-item d-none" id="cupon-container"', false)
            ->assertSee('id="hidden-total" value="550"', false);

        $this->assertNull(session('carrito_cupon'));
    }

    public function test_un_codigo_inyectado_en_la_sesion_no_crea_descuento(): void
    {
        $usuario = $this->crearClienteConCarrito();

        $this->withSession(['carrito_cupon' => 'FANTASMA'])
            ->actingAs($usuario)
            ->get(route('web.carrito.index'))
            ->assertSuccessful()
            ->assertSee('class="summary-item d-none" id="cupon-container"', false)
            ->assertSee('id="hidden-total" value="550"', false);

        $this->assertNull(session('carrito_cupon'));
    }

    public function test_un_descuento_mayor_a_noventa_por_ciento_se_topea(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->cupon(['codigo' => 'REGALO', 'descuento_porcentaje' => 500]);

        $this->aplicarCupon($usuario, 'REGALO')->assertSessionHas('icono', 'success');

        // 500 % sería gratis; el tope compartido con la app deja 90 %.
        $this->actingAs($usuario)
            ->get(route('web.carrito.index'))
            ->assertSee('−90%', false)
            ->assertSee('id="hidden-total" value="55"', false);
    }

    public function test_quitar_el_cupon_devuelve_el_precio_normal(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->cupon();

        $this->aplicarCupon($usuario, 'GALY10')->assertSessionHas('icono', 'success');
        $this->assertSame('GALY10', session('carrito_cupon'));

        $this->actingAs($usuario)
            ->from(route('web.carrito.index'))
            ->post(route('web.carrito.cupon'), ['codigo' => 'GALY10', 'quitar' => '1'])
            ->assertSessionHas('icono', 'success');

        $this->assertNull(session('carrito_cupon'));

        $this->actingAs($usuario)
            ->get(route('web.carrito.index'))
            ->assertSee('id="hidden-total" value="550"', false);
    }

    public function test_el_pedido_se_crea_con_el_importe_del_cupon_aplicado(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->cupon();
        $this->configurarPasarela();

        $this->aplicarCupon($usuario, 'GALY10');

        $this->actingAs($usuario)
            ->post(route('web.pedidos.pago_manual'), $this->datosCheckout('tarjeta'))
            ->assertRedirect(self::SANDBOX_URL);

        $orden = \App\Models\Orden::sole();
        $this->assertSame(495.0, round((float) $orden->total, 2));
        $this->assertSame('GALY10', $orden->cupon);
        $this->assertSame(55.0, round((float) $orden->descuento, 2));

        // Mercado Pago cobra el total ya descontado, no la suma de los productos.
        Http::assertSent(function ($request) {
            if ($request->url() !== 'https://api.mercadopago.com/checkout/preferences') {
                return true;
            }

            $datos = json_decode($request->body(), true);

            return (float) $datos['items'][0]['unit_price'] === 495.0;
        });

        // El cupón ya se usó: el siguiente carrito no puede aparecer descontado.
        $this->assertNull(session('carrito_cupon'));

        $this->assertStringContainsString(
            'Cupón GALY10',
            \App\Models\PedidoHistorial::sole()->nota
        );
    }

    public function test_no_se_crea_el_pedido_si_el_cupon_deja_de_ser_valido(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->cupon();

        $this->aplicarCupon($usuario, 'GALY10');
        $this->assertSame('GALY10', session('carrito_cupon'));

        DB::table('cupones')->update(['activo' => 0]);

        $this->actingAs($usuario)
            ->from(route('web.carrito.index'))
            ->post(route('web.pedidos.pago_manual'), $this->datosCheckout('yape'))
            ->assertRedirect(route('web.carrito.index'))
            ->assertSessionHas('icono', 'warning');

        // Fail closed: ni pedido, ni cobro, ni carrito vaciado.
        $this->assertDatabaseCount('pedidos', 0);
        $this->assertDatabaseCount('carrito', 1);
        $this->assertNull(session('carrito_cupon'));
    }

    public function test_sin_carrito_no_se_puede_aplicar_un_cupon(): void
    {
        $usuario = User::factory()->create(['galy_user_id' => 42, 'google_id' => 'google-42']);
        $this->cupon();

        $this->aplicarCupon($usuario, 'GALY10')
            ->assertRedirect(route('web.carrito.index'))
            ->assertSessionHas('mensaje', 'Agrega productos al carrito antes de usar un cupón.');

        $this->assertNull(session('carrito_cupon'));
    }

    public function test_un_invitado_no_puede_aplicar_cupones(): void
    {
        $this->post(route('web.carrito.cupon'), ['codigo' => 'GALY10'])
            ->assertRedirect(route('web.login'));

        $this->assertNull(session('carrito_cupon'));
    }
}
