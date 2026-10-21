<?php

namespace Tests\Feature;

use App\Mail\CompraConfirmada;
use App\Models\Departamento;
use App\Models\Direccion;
use App\Models\IntentoPagoMercadoPago;
use App\Models\Orden;
use App\Models\Pais;
use App\Models\PedidoHistorial;
use App\Models\Producto;
use App\Models\Provincia;
use App\Models\User;
use App\Services\MercadoPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Concerns\CreatesOrderPaymentTables;
use Tests\TestCase;

class MercadoPagoCheckoutTest extends TestCase
{
    use CreatesOrderPaymentTables;
    use RefreshDatabase;

    private const SANDBOX_URL = 'https://sandbox.mercadopago.com/checkout/primicia';

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearTablasDePagos();
    }

    private function configurarPasarela(bool $activaEnTienda = true, bool $verificada = true): void
    {
        config([
            'services.mercadopago.enabled' => true,
            'services.mercadopago.access_token' => 'TEST-8514936306504285-091415-3733c7f9ab12',
            'services.mercadopago.webhook_secret' => null,
            'services.mercadopago.currency' => 'PEN',
            'services.mercadopago.checkout_ttl_minutes' => 30,
        ]);

        DB::table('ajustes')->update(['web_mercadopago_activa' => $activaEnTienda ? 1 : 0]);

        if ($verificada) {
            app(MercadoPagoService::class)->registrarVerificacion();
        }
    }

    /**
     * Cliente con carrito de un solo producto (S/ 550), dirección guardada y los
     * tres métodos de transferencia configurados para el canal web.
     */
    private function crearClienteConCarrito(): User
    {
        $usuario = User::factory()->create([
            'galy_user_id' => 42,
            'google_id' => 'google-42',
            'email_verified_at' => now(),
        ]);

        $producto = Producto::factory()->create(['stock' => 5]);
        $producto->forceFill([
            'precio_venta' => 550,
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
            'web_bcp_cci' => '00110002000300040055',
            'web_bcp_titular' => 'Titular BCP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $peru = Pais::where('nombre', 'Perú')->firstOrFail();
        $departamento = Departamento::where('pais_id', $peru->id)->firstOrFail();
        $provincia = Provincia::where('departamento_id', $departamento->id)->firstOrFail();

        Direccion::create([
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

    private function simularPreferencia(): void
    {
        $intentos = 0;

        Http::fake([
            'api.mercadopago.com/checkout/preferences' => function () use (&$intentos) {
                $intentos++;

                return Http::response([
                    'id' => 'pref-'.(554 + $intentos),
                    'init_point' => 'https://www.mercadopago.com/checkout/produccion',
                    'sandbox_init_point' => self::SANDBOX_URL,
                ]);
            },
        ]);
    }

    /**
     * Mercado Pago ya genera credenciales de prueba que empiezan con `APP_USR-`.
     * Leer solo el prefijo manda al cliente al checkout de producción con un token
     * que no cobra, y el médico dice «modo producción» cuando es mentira.
     */
    public function test_a_test_credential_is_recognized_whatever_its_prefix(): void
    {
        $mercadoPago = app(MercadoPagoService::class);

        foreach ([
            'TEST-8514936306504285-091415-3733c7f9ab12d4e5',
            'APP_USR-8514936306504285-091415-3733c7f9ab12d4e5-TEST',
            'APP_USR-8514936306504285-TEST-3733c7f9ab12d4e5',
        ] as $token) {
            config(['services.mercadopago.access_token' => $token]);

            $this->assertTrue($mercadoPago->esTokenDePrueba(), "no se reconoció como prueba: {$token}");
        }

        config(['services.mercadopago.access_token' => 'APP_USR-8514936306504285-091415-3733c7f9ab12d4e5']);

        $this->assertFalse($mercadoPago->esTokenDePrueba());
    }

    /**
     * El filtro de forma está para rechazar la Public Key copiada por error, no
     * para rechazar credenciales auténticas. Las de Checkout Pro de hoy traen un
     * cuarto tramo numérico, y exigir tres dejaba fuera el token real del dueño.
     */
    public function test_the_shape_filter_accepts_a_real_token_and_rejects_a_public_key(): void
    {
        $mercadoPago = app(MercadoPagoService::class);

        foreach ([
            'TEST-8514936306504285-091415-3733c7f9ab12d4e5',
            'APP_USR-5684621234567890-1020-abcdef1234567890abcdef123456-1234567890',
        ] as $token) {
            config(['services.mercadopago.access_token' => $token]);

            $this->assertTrue($mercadoPago->tokenVerosimil(), "rechazado por forma: {$token}");
        }

        foreach ([
            'APP_USR-8f4a1b2c-3d4e-5f60-a1b2-c3d4e5f60718',
            'APP_USR-99999999999999999999999999999999',
        ] as $valor) {
            config(['services.mercadopago.access_token' => $valor]);

            $this->assertFalse($mercadoPago->tokenVerosimil(), "aceptado sin ser un token: {$valor}");
        }
    }

    /**
     * Lo que dice el prefijo no basta: la pestaña Prueba de Mercado Pago entrega
     * hoy tokens que empiezan con `APP_USR-`. La prueba contra la API devuelve la
     * cuenta titular, y esa es la que distingue un token que cobra de uno que no.
     */
    public function test_the_probe_tells_a_test_account_apart_from_a_real_one(): void
    {
        Http::fakeSequence()
            ->push(['id' => 1, 'email' => 'test_user_8119926368976578537@testuser.com'])
            ->push(['id' => 2, 'email' => 'tienda@ejemplo.com.pe']);

        $prueba = app(MercadoPagoService::class)->titularCuenta();

        $this->assertTrue($prueba['ok']);
        $this->assertTrue($prueba['es_prueba'], 'una cuenta @testuser.com no es de producción');

        $this->assertFalse(app(MercadoPagoService::class)->titularCuenta()['es_prueba']);
    }

    /**
     * Un token de la pestaña Prueba es válido y Mercado Pago lo acepta, pero con
     * él un cliente real no puede pagar. Dejar la tarjeta a la vista fabrica
     * clientes que llegan a un checkout que los rechaza y desisten.
     */
    public function test_a_verified_test_credential_is_not_offered_to_customers(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela(verificada: false);
        config(['services.mercadopago.access_token' => 'APP_USR-5684621234567890-1020-abcdef1234567890abcdef123456-1234567890']);
        app(MercadoPagoService::class)->registrarVerificacion(esPrueba: true);

        $mercadoPago = app(MercadoPagoService::class);

        $this->assertTrue($mercadoPago->credencialDePrueba());
        $this->assertFalse($mercadoPago->sePuedeOfrecer());

        $this->actingAs($usuario)
            ->get(route('web.carrito.index'))
            ->assertOk()
            ->assertSee('Próximamente')
            ->assertDontSee('id="pago_tarjeta"', false);
    }

    public function test_the_card_option_stays_hidden_without_gateway_credentials(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela();
        config(['services.mercadopago.access_token' => null]);

        $this->actingAs($usuario)
            ->get(route('web.carrito.index'))
            ->assertOk()
            ->assertSee('id="pago_yape"', false)
            ->assertDontSee('id="pago_tarjeta"', false);
    }

    public function test_the_card_option_stays_hidden_while_the_store_has_it_disabled(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela(activaEnTienda: false);

        $this->actingAs($usuario)
            ->get(route('web.carrito.index'))
            ->assertOk()
            ->assertDontSee('id="pago_tarjeta"', false);
    }

    public function test_the_checkout_offers_payment_methods_in_yape_plin_and_card_order(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela();

        $html = $this->actingAs($usuario)
            ->get(route('web.carrito.index'))
            ->assertOk()
            ->assertSee('Tarjeta Visa')
            // El dueño pidió Visa y solo Visa: ninguna marca más puede volver a
            // colarse en los textos que ve el cliente.
            ->assertDontSee('Mastercard')
            ->assertDontSee('Próximamente')
            ->assertSee('data-checkout-qr="tarjeta"', false)
            ->assertSee('id="payment-note-tarjeta"', false)
            // El cliente sale de la tienda al presionar el botón: si el panel no
            // dice que Mercado Pago tarda y que el cheque vence, cree que falló.
            ->assertSee('se abre Mercado Pago en esta misma pestaña')
            ->assertSee('El enlace vence en')
            ->assertDontSee('id="pago_bcp"', false)
            ->assertSee('Elige un método de pago')
            ->assertDontSee('Crear pedido y enviar comprobante')
            ->getContent();

        $posiciones = array_map(
            fn ($metodo) => strpos($html, 'id="pago_'.$metodo.'"'),
            ['yape', 'plin', 'tarjeta']
        );

        $this->assertStringNotContainsString('false', json_encode($posiciones));
        $this->assertSame($posiciones, (function (array $p) {
            sort($p);

            return $p;
        })($posiciones));
    }

    public function test_a_card_order_is_created_and_the_customer_is_sent_to_mercado_pago(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela();
        $this->simularPreferencia();

        $this->actingAs($usuario)
            ->post(route('web.pedidos.pago_manual'), $this->datosCheckout('tarjeta'))
            ->assertRedirect(self::SANDBOX_URL);

        $referencia = IntentoPagoMercadoPago::sole()->referencia_externa;

        Http::assertSent(function ($request) use ($referencia) {
            if ($request->url() !== 'https://api.mercadopago.com/checkout/preferences') {
                return true;
            }

            $datos = json_decode($request->body(), true);

            return (float) $datos['items'][0]['unit_price'] === 550.0
                && $datos['items'][0]['currency_id'] === 'PEN'
                && $datos['external_reference'] === $referencia
                && str_contains($datos['notification_url'], '/webhooks/mercadopago');
        });

        $orden = Orden::where('metodo_pago', 'tarjeta')->sole();
        $this->assertSame('Pendiente', $orden->estado_pago);
        $this->assertSame(550, (int) $orden->total);
        $this->assertDatabaseCount('pedido_pagos_manuales', 0);
        $this->assertSame(0, DB::table('carrito')->count());

        $intento = IntentoPagoMercadoPago::sole();
        $this->assertSame(IntentoPagoMercadoPago::ESTADO_ESPERANDO, $intento->estado);
        $this->assertSame('pref-555', $intento->mercadopago_order_id);
        $this->assertSame($orden->id, (int) $intento->pedido_id);
        $this->assertSame(self::SANDBOX_URL, $intento->checkout_url);
        $this->assertNotNull($intento->expira_at);
    }

    public function test_the_delivery_cost_follows_the_store_rules_when_the_order_is_created(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela();

        $datos = array_merge($this->datosCheckout('yape'), ['modalidad_entrega' => 'envio']);

        $this->actingAs($usuario)
            ->post(route('web.pedidos.pago_manual'), array_merge($datos, ['shipping_option' => 'express']))
            ->assertRedirect();

        // Carrito de S/ 550: el estándar ya es gratis, pero el urgente se cobra igual.
        $primeraOrden = Orden::sole();
        $this->assertSame(600.0, (float) $primeraOrden->total);

        DB::table('carrito')->insert([
            'usuario_id' => $usuario->galy_user_id,
            'producto_id' => $primeraOrden->detalles->first()->producto_id,
            'cantidad' => 1,
        ]);

        $this->actingAs($usuario)
            ->post(route('web.pedidos.pago_manual'), array_merge($datos, ['shipping_option' => 'standard']))
            ->assertRedirect();

        $this->assertSame(550.0, (float) Orden::find($primeraOrden->id + 1)->total);
    }

    public function test_the_gateway_can_be_pointed_at_a_public_url_different_from_the_site(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela();
        $this->simularPreferencia();
        config(['services.mercadopago.url_base' => 'https://tunel-publico.example']);

        $this->actingAs($usuario)
            ->post(route('web.pedidos.pago_manual'), $this->datosCheckout('tarjeta'))
            ->assertRedirect(self::SANDBOX_URL);

        $cuerpo = null;
        Http::assertSent(function ($request) use (&$cuerpo) {
            if ($request->url() !== 'https://api.mercadopago.com/checkout/preferences') {
                return true;
            }
            $cuerpo = json_decode($request->body(), true);

            return $cuerpo['notification_url'] === 'https://tunel-publico.example/webhooks/mercadopago'
                && $cuerpo['back_urls']['success'] === 'https://tunel-publico.example/pago/tarjeta/retorno?referencia='.$cuerpo['external_reference'].'&estado=success';
        });

        $this->assertIsArray($cuerpo);
    }

    public function test_the_cart_survives_when_mercado_pago_rejects_the_checkout(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela();

        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response(['message' => 'bad_request'], 400),
        ]);

        $this->actingAs($usuario)
            ->from(route('web.carrito.index'))
            ->post(route('web.pedidos.pago_manual'), $this->datosCheckout('tarjeta'))
            ->assertRedirect(route('web.carrito.index'))
            ->assertSessionHas('mensaje', 'No pudimos abrir el pago con tarjeta. Tu carrito sigue intacto: elige otro método o inténtalo de nuevo.');

        $this->assertSame(0, Orden::count());
        $this->assertSame(1, IntentoPagoMercadoPago::count());
        $this->assertSame(IntentoPagoMercadoPago::ESTADO_FALLIDO, IntentoPagoMercadoPago::sole()->estado);
        $this->assertSame(1, DB::table('carrito')->count());
    }

    /**
     * Un 401 o un 403 no se arregla reintentando: es la credencial. Dejar la
     * tarjeta a la vista fabrica clientes que pagan y ven un error.
     */
    public function test_a_credential_rejection_hides_the_card_option_afterwards(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela();

        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response(['message' => 'At least one policy returned UNAUTHORIZED'], 403),
        ]);

        $this->actingAs($usuario)
            ->post(route('web.pedidos.pago_manual'), $this->datosCheckout('tarjeta'))
            ->assertRedirect(route('web.carrito.index'));

        $this->assertSame(0, Orden::count());

        $this->actingAs($usuario)
            ->get(route('web.carrito.index'))
            ->assertOk()
            ->assertSee('id="pago_yape"', false)
            ->assertDontSee('id="pago_tarjeta"', false);
    }

    /**
     * Retirar la tarjeta sin decir nada deja al cliente con la duda y al dueño
     * volviendo a activar el interruptor. El aviso "Próximamente" no puede
     * enviarse: es un div, no un radio, así que no hay pago que pueda intentarse.
     */
    public function test_a_rejected_credential_shows_the_card_as_an_advisory_proximamente_label(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela();

        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response(['message' => 'At least one policy returned UNAUTHORIZED'], 403),
        ]);

        $this->actingAs($usuario)
            ->post(route('web.pedidos.pago_manual'), $this->datosCheckout('tarjeta'))
            ->assertRedirect(route('web.carrito.index'));

        $this->actingAs($usuario)
            ->get(route('web.carrito.index'))
            ->assertOk()
            ->assertSee('Próximamente')
            ->assertSee('Estamos activando este medio de pago')
            ->assertSee('id="pago_yape"', false)
            ->assertDontSee('id="pago_tarjeta"', false)
            ->assertDontSee('for="pago_tarjeta"', false);
    }

    /**
     * Sin credencial no hay tarjeta que anunciar: un "Próximamente" prometería
     * un medio de pago que nunca va a llegar.
     */
    public function test_the_proximamente_label_is_not_shown_without_a_configured_gateway(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela();
        config(['services.mercadopago.access_token' => null]);

        $this->actingAs($usuario)
            ->get(route('web.carrito.index'))
            ->assertOk()
            ->assertDontSee('Próximamente')
            ->assertDontSee('Estamos activando este medio de pago');
    }

    /**
     * El candado no puede vivir en la caché: un `cache:clear` borraba el bloqueo
     * y devolvía a la vitrina una tarjeta que da 403. La verificación está en la
     * base, así que vaciar la caché deja la tarjeta igual de retirada.
     */
    public function test_clearing_the_cache_does_not_put_a_never_verified_card_back_in_the_cart(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela(verificada: false);

        $this->assertFalse(app(MercadoPagoService::class)->sePuedeOfrecer());

        Cache::clear();

        $this->actingAs($usuario)
            ->get(route('web.carrito.index'))
            ->assertOk()
            ->assertDontSee('id="pago_tarjeta"', false);
    }

    /**
     * La prueba certificó un token concreto. Si mañana el `.env` lleva otro, la
     * certificación anterior no puede avalarlo: la tarjeta vuelve a retirarse
     * hasta que alguien corra `mercadopago:verificar --probar` con el nuevo.
     */
    public function test_changing_the_access_token_invalidates_the_recorded_verification(): void
    {
        $this->crearClienteConCarrito();
        $this->configurarPasarela();

        $this->assertTrue(app(MercadoPagoService::class)->credencialVerificada());

        config(['services.mercadopago.access_token' => 'TEST-8514936306504285-091415-ffffffffffff']);

        $this->assertFalse(app(MercadoPagoService::class)->credencialVerificada());
        $this->assertFalse(app(MercadoPagoService::class)->sePuedeOfrecer());
    }

    /**
     * Un 400 es culpa del payload que armamos nosotros, no de la credencial: el
     * próximo cliente sí podría pasar, así que la opción no se retira.
     */
    public function test_a_rejected_payload_leaves_the_card_option_visible(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela();

        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response(['message' => 'bad_request'], 400),
        ]);

        $this->actingAs($usuario)
            ->post(route('web.pedidos.pago_manual'), $this->datosCheckout('tarjeta'))
            ->assertRedirect(route('web.carrito.index'));

        $this->assertFalse(app(MercadoPagoService::class)->rechazoReciente());

        $this->actingAs($usuario)
            ->get(route('web.carrito.index'))
            ->assertOk()
            ->assertSee('id="pago_tarjeta"', false);
    }

    /**
     * El bloqueo de caché se retira solo, media hora. Pero con una credencial
     * muerta reaparecer cada media hora solo fabrica clientes que pagan y fallan,
     * así que al tercer token distinto rechazado la opción ya no vuelve sola. Y
     * vaciar la caché tampoco la reabre: lo que la devuelve es una prueba nueva.
     */
    public function test_the_card_option_stops_reappearing_after_three_rejections(): void
    {
        $this->crearClienteConCarrito();
        $this->configurarPasarela();
        $mercadoPago = app(MercadoPagoService::class);

        foreach (['aaaaaaaaaaaa', 'bbbbbbbbbbbb', 'cccccccccccc'] as $firma) {
            config(['services.mercadopago.access_token' => 'TEST-111111-2222-'.$firma]);
            $mercadoPago->registrarVerificacion();
            $mercadoPago->bloquearPorRechazo();
        }

        $this->assertTrue($mercadoPago->bloqueoDefinitivo());
        $this->assertFalse($mercadoPago->sePuedeOfrecer());

        $mercadoPago->levantarBloqueo();
        $this->assertFalse($mercadoPago->rechazoReciente());
        $this->assertFalse($mercadoPago->sePuedeOfrecer());

        $mercadoPago->registrarVerificacion();
        $this->assertTrue($mercadoPago->sePuedeOfrecer());
    }

    /**
     * El candado manda solo sobre la oferta. `habilitado()` también gobierna el
     * webhook y el reconciliador, y un 403 no puede dejar sin confirmar el cobro
     * de un cliente que ya pagó.
     */
    public function test_an_approved_charge_still_pays_the_order_while_the_card_option_is_locked(): void
    {
        [$orden, $intento] = $this->crearPedidoConTarjeta();

        app(MercadoPagoService::class)->bloquearPorRechazo();

        $this->aprobarPagoCon(987654, $intento, 550, 'PEN');

        $this->assertSame('Pagado', $orden->fresh()->estado_pago);
        $this->assertSame(IntentoPagoMercadoPago::ESTADO_CONFIRMADO, $intento->fresh()->estado);
    }

    public function test_the_webhook_marks_the_order_as_paid_when_the_payment_is_approved(): void
    {
        [$orden, $intento] = $this->crearPedidoConTarjeta();

        Http::fake([
            'api.mercadopago.com/v1/payments/*' => Http::response([
                'id' => 987654,
                'status' => 'approved',
                'status_detail' => 'accredited',
                'transaction_amount' => 550,
                'currency_id' => 'PEN',
                'external_reference' => $intento->referencia_externa,
            ]),
        ]);

        $this->post(route('web.hook.mercadopago'), [
            'type' => 'payment',
            'data' => ['id' => 987654],
        ])->assertNoContent();

        $this->assertSame('Pagado', $orden->fresh()->estado_pago);
        $this->assertSame(IntentoPagoMercadoPago::ESTADO_CONFIRMADO, $intento->fresh()->estado);
        $this->assertSame('approved', $intento->fresh()->estado_mercadopago);
        $this->assertNotNull($intento->fresh()->confirmado_at);
        $this->assertDatabaseHas('pedido_historials', [
            'orden_id' => $orden->id,
            'estado_anterior' => 'Pendiente',
        ]);
        $this->assertStringContainsString(
            'Se verificó el cobro de 550.00 PEN',
            PedidoHistorial::where('orden_id', $orden->id)->whereNotNull('estado_anterior')->value('nota')
        );
    }

    public function test_a_rejected_payment_never_marks_the_order_as_paid(): void
    {
        [$orden, $intento] = $this->crearPedidoConTarjeta();

        Http::fake([
            'api.mercadopago.com/v1/payments/*' => Http::response([
                'id' => 987654,
                'status' => 'rejected',
                'status_detail' => 'cc_rejected_insufficient_amount',
                'external_reference' => $intento->referencia_externa,
            ]),
        ]);

        $this->post(route('web.hook.mercadopago'), [
            'type' => 'payment',
            'data' => ['id' => 987654],
        ])->assertNoContent();

        $this->assertSame('Pendiente', $orden->fresh()->estado_pago);
        $this->assertSame(IntentoPagoMercadoPago::ESTADO_FALLIDO, $intento->fresh()->estado);
    }

    public function test_a_webhook_with_a_bad_signature_is_ignored(): void
    {
        [$orden, $intento] = $this->crearPedidoConTarjeta();
        config(['services.mercadopago.webhook_secret' => 'secreto-de-prueba']);

        Http::fake([
            'api.mercadopago.com/v1/payments/*' => Http::response([
                'id' => 987654,
                'status' => 'approved',
                'external_reference' => $intento->referencia_externa,
            ]),
        ]);

        $this->post(route('web.hook.mercadopago'), [
            'type' => 'payment',
            'data' => ['id' => 987654],
        ], ['x-signature' => 'ts=1700000000000,v1=0000000000000000000000000000000000000000'])
            ->assertNoContent();

        Http::assertNothingSent();
        $this->assertSame('Pendiente', $orden->fresh()->estado_pago);
        $this->assertSame(IntentoPagoMercadoPago::ESTADO_ESPERANDO, $intento->fresh()->estado);
    }

    public function test_a_correctly_signed_webhook_is_accepted(): void
    {
        [$orden, $intento] = $this->crearPedidoConTarjeta();
        $secreto = 'secreto-de-prueba';
        config(['services.mercadopago.webhook_secret' => $secreto]);

        Http::fake([
            'api.mercadopago.com/v1/payments/*' => Http::response([
                'id' => 987654,
                'status' => 'approved',
                'transaction_amount' => 550,
                'currency_id' => 'PEN',
                'external_reference' => $intento->referencia_externa,
            ]),
        ]);

        $marca = '1700000000000';
        $cabecera = 'ts='.$marca.',v1='.hash_hmac('sha256', 'id:987654;request-id:req-1;ts:'.$marca.';', $secreto);

        $this->post(route('web.hook.mercadopago'), [
            'type' => 'payment',
            'data' => ['id' => 987654],
        ], ['x-signature' => $cabecera, 'x-request-id' => 'req-1'])
            ->assertNoContent();

        $this->assertSame('Pagado', $orden->fresh()->estado_pago);
    }

    public function test_returning_from_mercado_pago_confirms_the_order_without_waiting_for_the_webhook(): void
    {
        [$orden, $intento] = $this->crearPedidoConTarjeta();
        $usuario = User::where('galy_user_id', 42)->sole();

        Http::fake([
            'api.mercadopago.com/v1/payments/search*' => Http::response([
                'results' => [[
                    'id' => 987654,
                    'status' => 'approved',
                    'transaction_amount' => 550,
                    'currency_id' => 'PEN',
                    'external_reference' => $intento->referencia_externa,
                ]],
            ]),
        ]);

        $this->actingAs($usuario)
            ->get(route('web.pagos.tarjeta.retorno', ['referencia' => $intento->referencia_externa]))
            ->assertRedirect(route('web.pedidos.confirmacion', $orden->id));

        $this->assertSame('Pagado', $orden->fresh()->estado_pago);
    }

    public function test_the_confirmation_page_asks_the_customer_to_finish_the_card_payment(): void
    {
        [$orden] = $this->crearPedidoConTarjeta();
        $usuario = User::where('galy_user_id', 42)->sole();

        $this->actingAs($usuario)
            ->get(route('web.pedidos.confirmacion', $orden->id))
            ->assertOk()
            ->assertSee('Falta confirmar tu pago con tarjeta')
            ->assertSee('Pagar ahora con tarjeta')
            ->assertSee('action="'.route('web.pedidos.tarjeta.reintentar', $orden->id).'"', false)
            ->assertDontSee('Total Pagado');
    }

    public function test_the_confirmation_page_stops_asking_once_mercado_pago_approves_the_payment(): void
    {
        [$orden, $intento] = $this->crearPedidoConTarjeta();
        $usuario = User::where('galy_user_id', 42)->sole();

        Http::fake([
            'api.mercadopago.com/v1/payments/search*' => Http::response([
                'results' => [[
                    'id' => 987654,
                    'status' => 'approved',
                    'transaction_amount' => 550,
                    'currency_id' => 'PEN',
                    'external_reference' => $intento->referencia_externa,
                ]],
            ]),
        ]);

        $this->actingAs($usuario)->get(route('web.pagos.tarjeta.retorno', ['referencia' => $intento->referencia_externa]));

        $this->actingAs($usuario)
            ->get(route('web.pedidos.confirmacion', $orden->id))
            ->assertOk()
            ->assertDontSee('Falta confirmar tu pago con tarjeta')
            ->assertSee('Total Pagado');
    }

    public function test_a_pending_card_order_can_reopen_the_checkout(): void
    {
        [$orden, $primerIntento] = $this->crearPedidoConTarjeta();
        $usuario = User::where('galy_user_id', 42)->sole();

        $this->simularPreferencia();

        $this->actingAs($usuario)
            ->post(route('web.pedidos.tarjeta.reintentar', $orden->id))
            ->assertRedirect(self::SANDBOX_URL);

        $this->assertSame(2, IntentoPagoMercadoPago::count());
        $this->assertSame(IntentoPagoMercadoPago::ESTADO_EXPIRADO, $primerIntento->fresh()->estado);
        $this->assertSame('pref-556', IntentoPagoMercadoPago::where('estado', IntentoPagoMercadoPago::ESTADO_ESPERANDO)->value('mercadopago_order_id'));
        $this->assertSame(1, IntentoPagoMercadoPago::where('estado', IntentoPagoMercadoPago::ESTADO_ESPERANDO)->count());
        $this->assertNotSame(
            $primerIntento->referencia_externa,
            IntentoPagoMercadoPago::where('estado', IntentoPagoMercadoPago::ESTADO_ESPERANDO)->value('referencia_externa')
        );
    }

    public function test_another_customer_cannot_reopen_someone_elses_checkout(): void
    {
        [$orden] = $this->crearPedidoConTarjeta();

        $ajeno = User::factory()->create([
            'galy_user_id' => 777,
            'google_id' => 'google-777',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($ajeno)
            ->post(route('web.pedidos.tarjeta.reintentar', $orden->id))
            ->assertNotFound();
    }

    /**
     * La referencia del intento es un UUID, pero mientras sea válida el pedido se
     * confirmaba aunque el retorno lo abriera otro cliente.
     */
    public function test_another_customer_cannot_open_the_return_page_for_someone_elses_payment(): void
    {
        [$orden, $intento] = $this->crearPedidoConTarjeta();

        $ajeno = User::factory()->create([
            'galy_user_id' => 777,
            'google_id' => 'google-777',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($ajeno)
            ->get(route('web.pagos.tarjeta.retorno', ['referencia' => $intento->referencia_externa]))
            ->assertRedirect(route('web.carrito.index'))
            ->assertSessionHas('mensaje', 'Ese pago no pertenece a tu cuenta.');

        $this->assertSame('Pendiente', $orden->fresh()->estado_pago);
        $this->assertSame(IntentoPagoMercadoPago::ESTADO_ESPERANDO, $intento->fresh()->estado);
    }

    public function test_a_payment_id_of_another_reference_never_pays_the_order(): void
    {
        [$orden, $intento] = $this->crearPedidoConTarjeta();
        $usuario = User::where('galy_user_id', 42)->sole();

        Http::fake([
            'api.mercadopago.com/v1/payments/search*' => Http::response(['results' => []]),
            'api.mercadopago.com/v1/payments/*' => Http::response([
                'id' => 111111,
                'status' => 'approved',
                'transaction_amount' => 550,
                'currency_id' => 'PEN',
                'external_reference' => 'la-referencia-de-otro-pedido',
            ]),
        ]);

        $this->actingAs($usuario)
            ->get(route('web.pagos.tarjeta.retorno', [
                'referencia' => $intento->referencia_externa,
                'payment_id' => 111111,
            ]))
            ->assertRedirect(route('web.pedidos.confirmacion', $orden->id));

        Http::assertSent(fn ($request) => $request->url() === 'https://api.mercadopago.com/v1/payments/111111');

        $this->assertSame('Pendiente', $orden->fresh()->estado_pago);
        $this->assertSame(IntentoPagoMercadoPago::ESTADO_ESPERANDO, $intento->fresh()->estado);
        $this->assertNull($intento->fresh()->mercadopago_payment_id);
    }

    public function test_an_approved_payment_of_a_different_amount_leaves_the_order_pending(): void
    {
        [$orden, $intento] = $this->crearPedidoConTarjeta();

        $this->aprobarPagoCon(987654, $intento, 5.5, 'PEN');

        $this->assertSame('Pendiente', $orden->fresh()->estado_pago);
        $this->assertSame(IntentoPagoMercadoPago::ESTADO_ESPERANDO, $intento->fresh()->estado);
        $this->assertStringContainsString('Pagado pero sin verificar', $intento->fresh()->detalle_estado_mercadopago);
        $this->assertStringContainsString(
            'no se pudo aplicar automáticamente',
            PedidoHistorial::where('orden_id', $orden->id)->orderByDesc('id')->value('nota')
        );
    }

    public function test_an_approved_payment_of_another_currency_leaves_the_order_pending(): void
    {
        [$orden, $intento] = $this->crearPedidoConTarjeta();

        $this->aprobarPagoCon(987655, $intento, 550, 'USD');

        $this->assertSame('Pendiente', $orden->fresh()->estado_pago);
        $this->assertSame('550.00', $intento->fresh()->monto);
        $this->assertStringContainsString('divisa cobrada (USD)', $intento->fresh()->detalle_estado_mercadopago);
    }

    /**
     * El mismo payment_id no puede pagar dos intentos: la columna es única, así que
     * reutilizarlo debía rechazarse con una nota, no con un error 500.
     */
    public function test_a_payment_that_already_confirmed_another_attempt_is_not_reused(): void
    {
        [$orden, $intento] = $this->crearPedidoConTarjeta();

        IntentoPagoMercadoPago::create([
            'usuario_id' => $intento->usuario_id,
            'firma_carrito' => $intento->firma_carrito,
            'monto' => $intento->monto,
            'divisa' => $intento->divisa,
            'estado' => IntentoPagoMercadoPago::ESTADO_CONFIRMADO,
            'mercadopago_payment_id' => '555555',
            'pedido_id' => $orden->id,
            'direccion_envio' => $intento->direccion_envio,
            'modalidad_entrega' => $intento->modalidad_entrega,
            'items' => $intento->items,
            'expira_at' => now()->addMinutes(30),
        ]);

        $this->aprobarPagoCon(555555, $intento, 550, 'PEN');

        $this->assertSame('Pendiente', $orden->fresh()->estado_pago);
        $this->assertSame(IntentoPagoMercadoPago::ESTADO_ESPERANDO, $intento->fresh()->estado);
        $this->assertNull($intento->fresh()->mercadopago_payment_id);
        $this->assertStringContainsString(
            'el pago ya confirmó otro intento',
            $intento->fresh()->detalle_estado_mercadopago
        );
    }

    public function test_the_customer_is_never_asked_to_pay_twice_while_a_charge_is_under_review(): void
    {
        [$orden, $intento] = $this->crearPedidoConTarjeta();
        $usuario = User::where('galy_user_id', 42)->sole();

        // Mercado Pago cobra de menos: el pedido queda en revisión, no pagado.
        $this->aprobarPagoCon(987654, $intento, 5.5, 'PEN');

        $this->actingAs($usuario)
            ->get(route('web.pedidos.confirmacion', $orden->id))
            ->assertOk()
            ->assertSee('Mercado Pago registró un cobro')
            ->assertSee('No vuelvas a pagar')
            ->assertDontSee('Pagar ahora con tarjeta');

        $this->actingAs($usuario)
            ->from(route('web.pedidos.confirmacion', $orden->id))
            ->post(route('web.pedidos.tarjeta.reintentar', $orden->id))
            ->assertRedirect(route('web.pedidos.confirmacion', $orden->id))
            ->assertSessionHas('mensaje', 'Mercado Pago ya registró un cobro para este pedido y la tienda lo está verificando. No repitas el pago.');

        $this->assertSame(1, IntentoPagoMercadoPago::count());
        $this->assertSame('Pendiente', $orden->fresh()->estado_pago);
    }

    public function test_the_order_is_not_paid_when_the_store_changed_its_total_after_the_checkout(): void
    {
        [$orden, $intento] = $this->crearPedidoConTarjeta();

        // El pedido costaba S/ 550 al abrir Mercado Pago y luego se le subió el precio.
        $orden->forceFill(['total' => 600])->save();

        $this->aprobarPagoCon(987654, $intento, 550, 'PEN');

        $this->assertSame('Pendiente', $orden->fresh()->estado_pago);
        $this->assertStringContainsString(
            'no coincide con el total actual del pedido (600)',
            PedidoHistorial::where('orden_id', $orden->id)->orderByDesc('id')->value('nota')
        );
    }

    public function test_a_second_approved_charge_on_a_paid_order_is_reported_instead_of_paid_again(): void
    {
        [$orden, $intento] = $this->crearPedidoConTarjeta();
        $orden->forceFill(['estado_pago' => 'Pagado'])->save();

        $this->aprobarPagoCon(987654, $intento, 550, 'PEN');

        $this->assertSame('Pagado', $orden->fresh()->estado_pago);
        $this->assertSame(IntentoPagoMercadoPago::ESTADO_CONFIRMADO, $intento->fresh()->estado);
        $this->assertStringContainsString(
            'pagó dos veces',
            PedidoHistorial::where('orden_id', $orden->id)->orderByDesc('id')->value('nota')
        );
    }

    /**
     * Un reintegro no puede pasar desapercibido: el pedido seguiría pagado en el
     * sistema mientras Mercado Pago ya le devolvió el dinero al cliente.
     */
    public function test_a_reversed_charge_on_a_paid_order_is_flagged_for_the_store(): void
    {
        [$orden, $intento] = $this->crearPedidoConTarjeta();
        $orden->forceFill(['estado_pago' => 'Pagado'])->save();

        Http::fake([
            'api.mercadopago.com/v1/payments/*' => Http::response([
                'id' => 987654,
                'status' => 'refunded',
                'external_reference' => $intento->referencia_externa,
            ]),
        ]);

        $this->post(route('web.hook.mercadopago'), [
            'type' => 'payment',
            'data' => ['id' => 987654],
        ])->assertNoContent();

        $this->assertSame('Pagado', $orden->fresh()->estado_pago);
        $this->assertSame(IntentoPagoMercadoPago::ESTADO_FALLIDO, $intento->fresh()->estado);
        $this->assertStringContainsString(
            'Mercado Pago revirtió el cobro (refunded)',
            PedidoHistorial::where('orden_id', $orden->id)->orderByDesc('id')->value('nota')
        );
    }

    public function test_the_reconcile_command_pays_an_order_whose_webhook_never_arrived(): void
    {
        [$orden, $intento] = $this->crearPedidoConTarjeta();

        Http::fake([
            'api.mercadopago.com/v1/payments/search*' => Http::response([
                'results' => [[
                    'id' => 987654,
                    'status' => 'approved',
                    'transaction_amount' => 550,
                    'currency_id' => 'PEN',
                    'external_reference' => $intento->referencia_externa,
                ]],
            ]),
        ]);

        $this->artisan('mercadopago:reconciliar')
            ->expectsOutput('Intentos revisados: 1')
            ->expectsOutput('Pagos confirmados: 1')
            ->assertSuccessful();

        $this->assertSame('Pagado', $orden->fresh()->estado_pago);
        $this->assertSame(IntentoPagoMercadoPago::ESTADO_CONFIRMADO, $intento->fresh()->estado);
    }

    public function test_the_reconcile_command_never_calls_the_gateway_without_credentials(): void
    {
        $this->crearClienteConCarrito();
        $this->configurarPasarela();
        config(['services.mercadopago.access_token' => null]);

        $this->artisan('mercadopago:reconciliar')
            ->expectsOutput('Mercado Pago no está configurado: no hay nada que conciliar.')
            ->assertSuccessful();
    }

    public function test_the_payment_routes_are_rate_limited(): void
    {
        $limites = [
            'web.pedidos.pago_manual' => 'throttle:10,1',
            'web.pedidos.comprobante' => 'throttle:10,1',
            'web.pagos.tarjeta.retorno' => 'throttle:30,1',
            'web.pedidos.tarjeta.reintentar' => 'throttle:6,1',
            'web.hook.mercadopago' => 'throttle:300,1',
        ];

        foreach ($limites as $nombre => $esperado) {
            $ruta = Route::getRoutes()->getByName($nombre);

            $this->assertNotNull($ruta, 'No existe la ruta '.$nombre.'.');
            $this->assertContains($esperado, $ruta->gatherMiddleware(), 'La ruta '.$nombre.' debería limitar a '.$esperado.'.');
        }
    }

    /**
     * Un doble clic en «Pagar» no puede duplicar el pedido: el segundo envío ya no
     * encuentra el carrito y se rechaza antes de escribir nada.
     */
    public function test_a_repeated_checkout_does_not_create_a_second_order(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela();

        $this->actingAs($usuario)->post(route('web.pedidos.pago_manual'), $this->datosCheckout('yape'))->assertRedirect();

        $this->actingAs($usuario)
            ->from(route('web.carrito.index'))
            ->post(route('web.pedidos.pago_manual'), $this->datosCheckout('yape'))
            ->assertRedirect(route('web.carrito.index'))
            ->assertSessionHas('mensaje', 'El carrito está vacío.');

        $this->assertSame(1, Orden::count());
        $this->assertSame(1, DB::table('pedido_pagos_manuales')->count());
    }

    /**
     * Dos envíos del mismo carrito (doble clic) no pueden dejar DOS cheques vivos
     * de Mercado Pago: el cliente paga por el link que la tienda ya dio por
     * perdido y ese cobro llega sin pedido asociado.
     */
    public function test_a_second_open_of_the_same_cart_reuses_the_pending_mercado_pago_checkout(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela();
        $this->simularPreferencia();

        $servicio = app(MercadoPagoService::class);
        $datos = [
            'usuario_id' => (int) $usuario->id,
            'items' => [['producto_id' => 1, 'nombre' => 'Laptop', 'cantidad' => 1, 'precio' => 550.0]],
            'direccion_id' => null,
            'direccion_envio' => 'Recojo en Tienda',
            'modalidad_entrega' => 'recojo',
            'monto' => 550.0,
            'divisa' => 'PEN',
            'correo' => $usuario->email,
        ];

        $primero = $servicio->abrirCheckout($datos);
        $segundo = $servicio->abrirCheckout($datos);

        // `intentos_pago_mercadopago` no tiene `id`: se identifica por referencia.
        $this->assertSame($primero->referencia_externa, $segundo->referencia_externa,
            'El mismo carrito debe reutilizar el cheque abierto.');
        $this->assertSame('pref-555', $segundo->mercadopago_order_id);
        $this->assertSame(1, IntentoPagoMercadoPago::count());

        // Un carrito distinto sí necesita su propio cheque: el filtro es la firma
        // del carrito, no "cualquier intento abierto de este cliente".
        $servicio->abrirCheckout([...$datos, 'monto' => 610.0]);
        $this->assertSame(2, IntentoPagoMercadoPago::count());

        // Y un cheque que ya quedó ligado a un pedido no se presta a otro: si el
        // cliente vuelve a armar el mismo carrito, su pago debe ser suyo.
        $primero->pedido_id = 777;
        $primero->save();

        $nuevo = $servicio->abrirCheckout($datos);
        $this->assertNotSame($primero->referencia_externa, $nuevo->referencia_externa);
        $this->assertSame(3, IntentoPagoMercadoPago::count());
    }

    /**
     * En recojo el cliente retira en el local: un pedido sin dirección registrada
     * es un pedido válido, y la pantalla ya no le pide una.
     */
    public function test_store_pickup_saves_the_order_without_any_address(): void
    {
        $usuario = $this->crearClienteConCarrito();
        Direccion::where('usuario_id', $usuario->id)->delete();

        $this->actingAs($usuario)
            ->from(route('web.carrito.index'))
            ->post(route('web.pedidos.pago_manual'), [
                'name' => 'Cliente GALY',
                'dni' => '12345678',
                'telefono' => '987654321',
                'privacy_consent' => '1',
                'metodo_pago' => 'yape',
                'modalidad_entrega' => 'recojo',
                'shipping_option' => 'pickup',
            ])
            ->assertRedirect();

        $pedido = Orden::firstOrFail();

        $this->assertNull($pedido->direccion_id);
        $this->assertSame('Recojo en Tienda', $pedido->direccion_envio);
        $this->assertSame('recojo', $pedido->modalidad_entrega);
        // Recojo no suma envío: el total es exactamente el subtotal del carrito.
        $this->assertSame(550.0, round((float) $pedido->total, 2));
    }

    public function test_the_customer_receives_an_email_when_the_order_is_registered(): void
    {
        Mail::fake();

        $usuario = $this->crearClienteConCarrito();

        $this->actingAs($usuario)->post(route('web.pedidos.pago_manual'), $this->datosCheckout('yape'));

        Mail::assertSent(
            fn (CompraConfirmada $correo) => $correo->hasTo($usuario->email)
                && $correo->orden->id === Orden::first()->id
        );
    }

    private function aprobarPagoCon(int $pagoId, IntentoPagoMercadoPago $intento, float $monto, string $divisa): void
    {
        Http::fake([
            'api.mercadopago.com/v1/payments/*' => Http::response([
                'id' => $pagoId,
                'status' => 'approved',
                'status_detail' => 'accredited',
                'transaction_amount' => $monto,
                'currency_id' => $divisa,
                'external_reference' => $intento->referencia_externa,
            ]),
        ]);

        $this->post(route('web.hook.mercadopago'), [
            'type' => 'payment',
            'data' => ['id' => $pagoId],
        ])->assertNoContent();
    }

    /** @return array{0: \App\Models\Orden, 1: \App\Models\IntentoPagoMercadoPago} */
    private function crearPedidoConTarjeta(): array
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela();
        $this->simularPreferencia();

        $this->actingAs($usuario)->post(route('web.pedidos.pago_manual'), $this->datosCheckout('tarjeta'));

        return [Orden::sole(), IntentoPagoMercadoPago::sole()];
    }
}
