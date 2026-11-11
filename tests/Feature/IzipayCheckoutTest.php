<?php

namespace Tests\Feature;

use App\Models\Orden;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\CreatesOrderPaymentTables;
use Tests\TestCase;

class IzipayCheckoutTest extends TestCase
{
    use CreatesOrderPaymentTables;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearTablasDePagos();
    }

    private function configurarIzipay(bool $activa = true): void
    {
        config([
            'services.izipay.enabled' => true,
            'services.izipay.shop_id' => 'SHOP-TEST',
            'services.izipay.password' => 'PASS-TEST',
            'services.izipay.public_key' => 'PUB-TEST',
            'services.izipay.hmac_key' => 'HMAC-TEST',
            'services.izipay.environment' => 'production',
            'services.izipay.checkout_ttl_minutes' => 30,
            'services.izipay.url_base' => 'http://127.0.0.1:8000',
        ]);

        DB::table('ajustes')->update(['web_izipay_activa' => $activa ? 1 : 0]);
    }

    private function crearClienteConCarrito(): \App\Models\User
    {
        $usuario = \App\Models\User::factory()->create([
            'galy_user_id' => 42,
            'google_id' => 'google-42',
            'email_verified_at' => now(),
        ]);

        $producto = \App\Models\Producto::factory()->create(['stock' => 5]);
        $producto->forceFill(['precio_venta' => 550, 'precio_oferta' => null, 'en_oferta' => false])->save();

        DB::connection('galy')->table('productos')->insert([
            'id' => $producto->id,
            'categoria_id' => $producto->categoria_id,
            'stock' => 5,
        ]);

        DB::table('ajustes')->insert([
            'nombre' => 'GALY', 'descripcion' => 'Tienda', 'sucursal' => 'Principal',
            'direccion' => 'Dirección', 'telefono' => '999999999', 'logo' => 'logo.png',
            'imagen_login' => 'login.png', 'email' => 'tienda@example.com', 'divisa' => 'PEN',
            'web_yape_numero' => '987654321', 'web_yape_titular' => 'Titular Yape',
            'web_yape_qr' => 'pagos/qr/web/yape.png', 'web_plin_numero' => '912345678',
            'web_plin_titular' => 'Titular Plin', 'web_plin_qr' => 'pagos/qr/web/plin.png',
            'web_bcp_cci' => '00110002000300040055', 'web_bcp_titular' => 'Titular BCP',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $peru = \App\Models\Pais::where('nombre', 'Perú')->firstOrFail();
        $departamento = \App\Models\Departamento::where('pais_id', $peru->id)->firstOrFail();
        $provincia = \App\Models\Provincia::where('departamento_id', $departamento->id)->firstOrFail();

        \App\Models\Direccion::create([
            'usuario_id' => $usuario->id, 'alias' => 'Casa', 'pais_id' => $peru->id,
            'departamento_id' => $departamento->id, 'provincia_id' => $provincia->id,
            'direccion_exacta' => 'Calle Falsa 123', 'es_predeterminada' => true,
        ]);

        DB::table('carrito')->insert([
            'usuario_id' => $usuario->galy_user_id,
            'producto_id' => $producto->id,
            'cantidad' => 1,
        ]);

        return $usuario;
    }

    private function datosCheckout(): array
    {
        return [
            'name' => 'Cliente GALY', 'dni' => '12345678', 'telefono' => '987654321',
            'privacy_consent' => '1', 'direccion_id' => 1, 'metodo_pago' => 'izipay',
            'modalidad_entrega' => 'recojo',
        ];
    }

    public function test_the_card_option_stays_hidden_without_credentials(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarIzipay(activa: false);

        $this->actingAs($usuario)
            ->get(route('web.carrito.index'))
            ->assertOk()
            ->assertDontSee('id="pago_izipay"', false);
    }

    public function test_the_card_option_appears_when_izipay_is_configured(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarIzipay();

        Http::fake([
            'api.micuentaweb.pe/api-payment/V4/Charge/CreatePayment' => Http::response([
                'status' => 'SUCCESS',
                'answer' => ['formToken' => 'FORMTOKEN-XYZ'],
            ]),
        ]);

        $this->actingAs($usuario)
            ->get(route('web.carrito.index'))
            ->assertOk()
            ->assertSee('id="pago_izipay"', false);
    }

    public function test_creating_the_order_redirects_to_the_izipay_form(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarIzipay();

        Http::fake([
            'api.micuentaweb.pe/api-payment/V4/Charge/CreatePayment' => Http::response([
                'status' => 'SUCCESS',
                'answer' => ['formToken' => 'FORMTOKEN-XYZ'],
            ]),
        ]);

        $response = $this->actingAs($usuario)
            ->post(route('web.pedidos.pago_manual'), $this->datosCheckout());

        $response->assertRedirect();
        $this->assertStringContainsString('/pago/izipay/', (string) $response->headers->get('Location'));

        $intento = DB::table('intentos_pago_izipay')->first();
        $this->assertNotNull($intento);
        $this->assertSame('FORMTOKEN-XYZ', $intento->form_token);

        $orden = Orden::first();
        $this->assertNotNull($orden);
        $this->assertSame('izipay', $orden->metodo_pago);
        $this->assertSame('Pendiente', $orden->estado_pago);
    }

    public function test_the_izipay_form_page_renders_the_embedded_form(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarIzipay();

        Http::fake([
            'api.micuentaweb.pe/api-payment/V4/Charge/CreatePayment' => Http::response([
                'status' => 'SUCCESS',
                'answer' => ['formToken' => 'FORMTOKEN-XYZ'],
            ]),
        ]);

        $this->actingAs($usuario)->post(route('web.pedidos.pago_manual'), $this->datosCheckout());

        $intento = DB::table('intentos_pago_izipay')->first();

        $this->actingAs($usuario)
            ->get(route('web.pagos.izipay.form', $intento->id))
            ->assertOk()
            ->assertSee('FORMTOKEN-XYZ', false)
            ->assertSee('kr-embedded', false)
            ->assertSee('kr-payment-button', false);
    }

    public function test_a_valid_return_marks_the_order_as_paid(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarIzipay();

        Http::fake([
            'api.micuentaweb.pe/api-payment/V4/Charge/CreatePayment' => Http::response([
                'status' => 'SUCCESS',
                'answer' => ['formToken' => 'FORMTOKEN-XYZ'],
            ]),
        ]);

        $this->actingAs($usuario)->post(route('web.pedidos.pago_manual'), $this->datosCheckout());

        $intento = DB::table('intentos_pago_izipay')->first();
        $orden = Orden::first();

        $respuesta = [
            'orderStatus' => 'PAID',
            'orderDetails' => [
                'orderId' => $intento->referencia,
                'orderTotalAmount' => 55000,
                'orderCurrency' => 'PEN',
            ],
            'transactions' => [[
                'uuid' => 'TX-IZIPAY-1',
                'status' => 'AUTHORISED',
                'paymentMethodType' => 'CARD',
            ]],
        ];
        $json = json_encode($respuesta);
        $firma = hash_hmac('sha256', $json, 'HMAC-TEST');

        $this->post(route('web.pagos.izipay.retorno'), [
            'kr-answer' => $json,
            'kr-hash' => $firma,
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'sha256_hmac',
        ])->assertRedirect();

        $orden->refresh();
        $this->assertSame('Pagado', $orden->estado_pago);
        $this->assertSame('TX-IZIPAY-1', $orden->transaccion_id);

        $intento = DB::table('intentos_pago_izipay')->first();
        $this->assertSame('confirmado', $intento->estado);
    }

    public function test_an_invalid_signature_does_not_touch_the_order(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarIzipay();

        Http::fake([
            'api.micuentaweb.pe/api-payment/V4/Charge/CreatePayment' => Http::response([
                'status' => 'SUCCESS',
                'answer' => ['formToken' => 'FORMTOKEN-XYZ'],
            ]),
        ]);

        $this->actingAs($usuario)->post(route('web.pedidos.pago_manual'), $this->datosCheckout());

        $intento = DB::table('intentos_pago_izipay')->first();
        $orden = Orden::first();

        $respuesta = [
            'orderStatus' => 'PAID',
            'orderDetails' => ['orderId' => $intento->referencia, 'orderTotalAmount' => 55000, 'orderCurrency' => 'PEN'],
            'transactions' => [['uuid' => 'TX-FAKE', 'status' => 'AUTHORISED', 'paymentMethodType' => 'CARD']],
        ];

        $this->post(route('web.pagos.izipay.retorno'), [
            'kr-answer' => json_encode($respuesta),
            'kr-hash' => 'firma-incorrecta',
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'sha256_hmac',
        ])->assertStatus(403);

        $orden->refresh();
        $this->assertSame('Pendiente', $orden->estado_pago);
    }

    public function test_the_ipn_confirms_the_payment_with_the_password_key(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarIzipay();

        Http::fake([
            'api.micuentaweb.pe/api-payment/V4/Charge/CreatePayment' => Http::response([
                'status' => 'SUCCESS',
                'answer' => ['formToken' => 'FORMTOKEN-XYZ'],
            ]),
        ]);

        $this->actingAs($usuario)->post(route('web.pedidos.pago_manual'), $this->datosCheckout());

        $intento = DB::table('intentos_pago_izipay')->first();

        $respuesta = [
            'orderStatus' => 'PAID',
            'orderDetails' => ['orderId' => $intento->referencia, 'orderTotalAmount' => 55000, 'orderCurrency' => 'PEN'],
            'transactions' => [['uuid' => 'TX-IPN-1', 'status' => 'AUTHORISED', 'paymentMethodType' => 'CARD']],
        ];
        $json = json_encode($respuesta);
        // La IPN firma con la clave API (password), no con la HMAC.
        $firma = hash_hmac('sha256', $json, 'PASS-TEST');

        $this->post(route('web.hook.izipay'), [
            'kr-answer' => $json,
            'kr-hash' => $firma,
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'password',
        ])->assertOk();

        $intento = DB::table('intentos_pago_izipay')->first();
        $this->assertSame('confirmado', $intento->estado);
    }
}
