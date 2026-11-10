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
use Illuminate\Http\Client\ConnectionException;
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

    private function configurarPasarela(bool $activaEnTienda = true, bool $verificada = true, string $urlBase = 'https://tienda.example'): void
    {
        config([
            'services.mercadopago.enabled' => true,
            'services.mercadopago.access_token' => 'TEST-8514936306504285-091415-3733c7f9ab12',
            'services.mercadopago.webhook_secret' => null,
            'services.mercadopago.currency' => 'PEN',
            'services.mercadopago.checkout_ttl_minutes' => 30,
            'services.mercadopago.url_base' => $urlBase,
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

    public function test_la_espera_del_pago_deja_una_salida_a_los_pedidos_del_cliente(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela();

        $this->actingAs($usuario)
            ->get(route('web.carrito.index'))
            ->assertOk()
            // Un botón bloqueado sin salida deja al cliente mirando un spinner.
            // Cancelar es solo navegar, y el destino tiene que ser donde vería el
            // pedido si el POST llegó a completarse.
            ->assertSee('Cancelar y ver mis pedidos')
            ->assertSee('id="checkout-espera"', false)
            ->assertSee('data-checkout-espera-larga', false)
            ->assertSee('no se te cobra dos veces')
            ->assertSee(route('web.dashboard'), false);
    }

    public function test_the_checkout_offers_payment_methods_in_card_then_qr_and_yape_order(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela();

        $html = $this->actingAs($usuario)
            ->get(route('web.carrito.index'))
            ->assertOk()
            ->assertSee('Tarjeta de crédito y/o débito')
            // Las marcas de la franja no son decorado: son las que la API
            // /v1/payment_methods de Mercado Pago devuelve activas para esta
            // cuenta (Visa, Mastercard, American Express y Diners). Anunciar
            // una que la pasarela no aprueba —Cabal, en este caso— manda al
            // cliente a una pantalla de Mercado Pago donde no puede pagar.
            ->assertSee('Mastercard')
            ->assertSee('American Express')
            ->assertSee('Diners Club')
            ->assertDontSee('Cabal')
            ->assertSee('Código QR')
            ->assertSee('Pago con Yape')
            ->assertDontSee('Próximamente')
            ->assertSee('data-checkout-qr="tarjeta"', false)
            ->assertSee('id="payment-note-tarjeta"', false)
            // El interrogante del modal existe para la pregunta que llega por
            // WhatsApp: «ya pagué, ¿y mi pedido?». Tarjeta confirma sola, Yape
            // y Plin no, y eso tiene que estar escrito en la pantalla.
            ->assertSee('data-modal-ayuda', false)
            ->assertSee('¿Cómo se confirma mi pago?')
            // El cliente sale de la tienda al presionar el botón: si el panel no
            // dice que Mercado Pago tarda y que el cheque vence, cree que falló.
            ->assertSee('Mercado Pago en esta misma pestaña')
            ->assertSee('El enlace vence en')
            // El dueño pidió el QR en esta pantalla. No se puede imprimir antes del
            // pedido, así que el paso de tarjeta ofrece «Generar QR de pago»; el
            // atajo de la página queda solo como salida tras un error de validación
            // y no se duplica dentro del modal.
            ->assertSee('Pagar desde mi celular')
            ->assertSee('name="pago_celular" value="1"', false)
            ->assertDontSee('data-modal-celular', false)
            ->assertDontSee('id="pago_bcp"', false)
            ->assertSee('Elige un método de pago')
            ->assertDontSee('Crear pedido y enviar comprobante')
            ->getContent();

        // La referencia que eligió el dueño abre con la tarjeta (confirma al
        // instante), sigue con el código QR y deja Yape al final.
        $posiciones = array_map(
            fn ($metodo) => strpos($html, 'id="pago_'.$metodo.'"'),
            ['tarjeta', 'plin', 'yape']
        );

        $this->assertStringNotContainsString('false', json_encode($posiciones));
        $this->assertSame($posiciones, (function (array $p) {
            sort($p);

            return $p;
        })($posiciones));
    }

    /**
     * Sin credenciales ni QR cargados la ventana quedaba en blanco: el cliente
     * leía una pantalla rota y escribía al dueño para preguntar si era su
     * conexión. Ahora el vacío se explica y da el teléfono de la tienda.
     */
    public function test_the_modal_explains_itself_when_no_payment_method_is_ready(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela(activaEnTienda: false);

        DB::table('ajustes')->update([
            'web_yape_numero' => null,
            'web_yape_titular' => null,
            'web_yape_qr' => null,
            'web_plin_numero' => null,
            'web_plin_titular' => null,
            'web_plin_qr' => null,
        ]);

        $this->actingAs($usuario)
            ->get(route('web.carrito.index'))
            ->assertOk()
            ->assertSee('Todavía no hay ningún medio de pago activo en esta tienda.')
            ->assertSee('Escríbenos al')
            ->assertSee('999999999')
            ->assertDontSee('id="pago_yape"', false)
            ->assertDontSee('id="pago_plin"', false)
            ->assertDontSee('Próximamente');
    }

    /**
     * La ventana abre con el medio que el cliente usó la última vez, pero eso lo
     * decide el JavaScript: si el radio viniera marcado desde el HTML, un cliente
     * sin scripts pagaría con tarjeta sin haber elegido nada, y el candado del
     * «ENG/ESP» volvió a colarse una vez como adorno sin función.
     */
    public function test_the_server_leaves_the_method_unselected_and_the_fake_controls_out(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela();

        $html = $this->actingAs($usuario)
            ->get(route('web.carrito.index'))
            ->assertOk()
            ->assertDontSee('ENG')
            ->assertSee('checkout-payment-modal__ayuda', false)
            ->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/<input[^>]*name="metodo_pago"[^>]*\bchecked\b/i',
            $html,
            'Ningún medio de pago puede salir marcado del servidor: marcarlo es decisión del cliente.'
        );

        // Las marcas viajan con el radio para que el paso 2 las repita sin
        // escribirlas a mano en dos lugares.
        $this->assertMatchesRegularExpression(
            '/data-marcas="Visa\|Mastercard\|American Express\|Diners Club"/',
            $html
        );
    }

    /**
     * El enlace de soporte se abre en WhatsApp, que exige el número en formato
     * internacional: el «0» de larga distancia y la falta del 51 producían un
     * chat inexistente justo cuando el cliente ya estaba teniendo problemas.
     */
    public function test_the_support_link_normalizes_the_phone_for_whatsapp(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela();
        DB::table('ajustes')->update(['telefono' => '0998765432']);

        $this->actingAs($usuario)
            ->get(route('web.carrito.index'))
            ->assertOk()
            ->assertSee('https://wa.me/51998765432', false)
            ->assertSee('¿Problemas para pagar? Escríbenos al', false)
            ->assertDontSee('https://wa.me/0998765432', false);
    }

    /**
     * El modal cierra tres huecos que se veían solo en la pantalla: el monto no
     * decía qué estaba pagando, el paso 2 dejaba adivinar cuántos toques faltaban
     * y bajo el botón verde no había quien respondiera «¿quién cobra mi tarjeta?».
     */
    public function test_the_modal_says_what_is_being_paid_and_how_the_payment_ends(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela();

        $nombre = Producto::firstOrFail()->nombre;

        $html = $this->actingAs($usuario)
            ->get(route('web.carrito.index'))
            ->assertOk()
            ->assertSee('1 artículo · '.$nombre)
            ->assertSee('data-detalle-pasos hidden', false)
            ->assertSee('checkout-payment-modal__confianza', false)
            ->getContent();

        // Cada medio del paso 2 tiene su propia secuencia y su propia línea de
        // confianza: la tarjeta cobra sola y Yape/Plin se verifican a mano, así
        // que prometer lo mismo a los dos mentiría en la última pantalla.
        $this->assertSame(3, preg_match_all('/pasos:\s*(\[[^\]]*\])/s', $html, $listas));
        $this->assertSame(3, substr_count($html, 'confianza:'));

        foreach ($listas[1] as $posicion => $pasos) {
            $this->assertSame(
                3,
                preg_match_all('/\'[^\']*\'|"[^"]*"/', $pasos),
                'el paso '.($posicion + 1).' debe cerrarse en tres instrucciones, ni una más'
            );
        }

        // La tarjeta tiene dos textos según dónde se escriba el número: si el
        // formulario vive en la tienda no se le puede prometer al cliente que va
        // a salir a Mercado Pago.
        $this->assertStringContainsString('Te llevamos a Mercado Pago en esta misma', $html);
        $this->assertStringNotContainsString('aparece el formulario de Mercado Pago', $html);

        // Bootstrap y las reglas propias de display ganan sobre el atributo
        // hidden: sin el !important los pasos vacíos quedarían pegados bajo
        // el botón verde.
        $ocultar = '';

        preg_match_all('/([^{}]+)\{([^}]*display:\s*none\s*!important[^}]*)\}/', $html, $reglas);
        $ocultar = implode(' ', $reglas[1]);

        foreach (['.checkout-payment-modal__ayuda-lista[hidden]', '.checkout-payment-modal__confianza[hidden]',
            '.checkout-payment-modal__recordatorio[hidden]', '.checkout-payment-modal__franja[hidden]'] as $selector) {
            $this->assertStringContainsString(
                $selector,
                $ocultar,
                $selector.' necesita !important para ocultarse o aparece vacío en el paso 2.'
            );
        }

        $this->assertMatchesRegularExpression(
            '/confianza\.hidden\s*=\s*paso\s*===\s*3/',
            $html,
            'el panel de ayuda ya explica la confianza y la línea del botón no debe repetirla.'
        );

        // Forma de la pantalla de Mercado Pago: ventana angosta de una columna.
        // El modal-lg medía 800 px y las filas se estiraban a tres columnas.
        $this->assertMatchesRegularExpression(
            '/<div class="modal-dialog(?![^"]*modal-lg)[^"]*checkout-payment-modal__dialog"[^>]*>/',
            $html,
            'el diálogo del modal debe arrancar angosto y sin modal-lg.'
        );

        // El ensanche del paso 2 lo decide el JavaScript sobre el .modal-dialog,
        // que es hijo del modal: tomado con closest() devolvía null y la pantalla
        // del QR se quedaba en 416 px con el código aplastado.
        $this->assertMatchesRegularExpression(
            "/modalMetodos\.querySelector\('\.modal-dialog'\)(?:.|\n){0,220}?classList\.toggle\('checkout-payment-modal__dialog--pago',\s*paso === 2\)/",
            $html,
            'el paso del QR tiene que ensanchar el diálogo, y para eso necesita el .modal-dialog correcto.'
        );

        // Las tres filas comparten la misma tarjeta (icono, título, subtítulo y
        // selector): la jerarquía ya no cambia según el método. Cada una debe
        // conservar su pista, que es lo que el cliente compara de un vistazo.
        $filas = [];

        preg_match_all('/<label class="([^"]*)" for="pago_(\w+)"/', $html, $filas);

        $this->assertSame(['tarjeta', 'plin', 'yape'], $filas[2]);

        foreach ($filas[1] as $clase) {
            $this->assertSame('checkout-method-card', trim($clase), 'todos los métodos usan la misma tarjeta.');
        }

        $this->assertStringNotContainsString('checkout-method-card--linea', $html);
        $this->assertStringNotContainsString('checkout-method-card--franja', $html);

        foreach (['Realiza tu pago en cuotas o directo', 'Usando tu billetera electrónica, con Plin', 'Usando tu billetera electrónica'] as $pista) {
            $this->assertStringContainsString($pista, $html);
        }

        // Las chapitas sin logotipo se escriben con su sigla: el nombre completo
        // («American Express») medía 90 px y rompía la franja en dos líneas.
        $this->assertStringContainsString('>AMEX<', $html);
        $this->assertStringContainsString('>DINERS<', $html);
        $this->assertStringContainsString('data-marcas="Visa|Mastercard|American Express|Diners Club"', $html);

        // Lo que Mercado Pago avisa antes de cobrar —y el motivo real de la
        // mayoría de rechazos— y las marcas bajo el botón verde de la tarjeta.
        $this->assertStringContainsString('data-detalle-recordatorio', $html);
        $this->assertStringContainsString('activa las compras por internet', $html);
        $this->assertMatchesRegularExpression(
            "/chips\.hidden\s*=\s*paso !== 2 \|\| metodo !== 'tarjeta'/",
            $html,
            'la franja de marcas solo debe verse bajo el botón de la tarjeta.'
        );

        // La pantalla de la tarjeta es de Mercado Pago, nunca nuestra: un campo
        // propio de número, vencimiento o CVV mete la tienda en alcance PCI y
        // le pide al cliente datos que esta integración no puede guardar.
        $this->assertDoesNotMatchRegularExpression(
            '/<input[^>]+(?:name|id)="[^"]*(?:cvv|vencimiento|numero[_-]?tarjeta|tarjeta[_-]?(?:numero|mes|a[nñ]o))"/i',
            $html,
            'el checkout no puede tener campos propios de tarjeta.'
        );

        // Sin Public Key no se anuncia un formulario que no existe: la pantalla
        // dice que la tarjeta se escribe en Mercado Pago y no pinta ningún brick.
        // (El nombre aparece en el CSS del formulario, que es inerte; lo que no
        // puede existir es su contenedor.)
        $this->assertStringNotContainsString('id="cardPaymentBrickModal"', $html);
        $this->assertStringContainsString('aquí no se escribe ninguna tarjeta', $html);
        $this->assertStringNotContainsString('Número de Tarjeta', $html);
        $this->assertStringNotContainsString('Así se verá el formulario de Mercado Pago', $html);

        // Con dos productos en el carrito la línea no puede quedarse en el
        // primero: el cliente tiene que ver que falta algo más por pagar.
        $otro = Producto::factory()->create(['stock' => 5]);
        $otro->forceFill(['precio_venta' => 250, 'precio_oferta' => null, 'en_oferta' => false])->save();

        DB::connection('galy')->table('productos')->insert([
            'id' => $otro->id,
            'categoria_id' => $otro->categoria_id,
            'stock' => 5,
        ]);

        DB::table('carrito')->insert([
            'usuario_id' => $usuario->galy_user_id,
            'producto_id' => $otro->id,
            'cantidad' => 2,
        ]);

        $this->actingAs($usuario)
            ->get(route('web.carrito.index'))
            ->assertOk()
            ->assertSee('3 artículos')
            ->assertSee('y 1 producto más');
    }

    /**
     * El paso de tarjeta no muestra el QR de Yape como si fuera el de la
     * tarjeta: ofrece el QR real de Mercado Pago junto al formulario, y el
     * pedido solo se crea cuando el cliente pide el código. El enlace de
     * privacidad y el sello PCI cierran la pantalla como en Mercado Pago.
     */
    public function test_the_card_step_offers_the_real_mercado_pago_qr_alongside_the_form(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela();

        $html = $this->actingAs($usuario)
            ->get(route('web.carrito.index'))
            ->assertOk()
            ->assertSee('data-detalle-tarjeta-qr', false)
            ->assertSee('data-tarjeta-qr-generar', false)
            ->assertSee('data-tarjeta-qr-imagen', false)
            ->assertSee('data-tarjeta-qr-enlace', false)
            ->assertSee('Generar QR de pago')
            ->assertDontSee('data-modal-celular', false)
            ->assertSee('Escanea el QR y paga con', false)
            ->assertSee('Abrir Mercado Pago')
            ->assertSee('Ver mi pedido')
            ->assertSee('Infórmate sobre el', false)
            ->assertSee(route('web.privacidad'), false)
            ->assertSee('PCI DSS', false)
            ->getContent();

        // El QR de la billetera ya no se cuela en el cobro con tarjeta: Yape y
        // Plin siguen siendo métodos propios dentro de la lista.
        $this->assertStringNotContainsString('data-detalle-atajo="yape"', $html);
        $this->assertStringNotContainsString('Pagar con Yape</button>', $html);

        // El QR no se pide al abrir el paso: solo lo dispara su botón, y esa
        // petición es la que crea el pedido.
        $this->assertStringContainsString("datos.set('qr_tarjeta', '1')", $html);
        $this->assertStringContainsString('function solicitarQrTarjeta()', $html);
        $this->assertStringContainsString("botonGenerarQr?.addEventListener('click', solicitarQrTarjeta)", $html);
        $this->assertStringNotContainsString('prepararQrTarjeta()', $html);
        $this->assertDoesNotMatchRegularExpression(
            '/paso === 2 && metodo === .tarjeta.\)\s*\{\s*solicitarQrTarjeta\(\)/',
            $html
        );

        $this->assertStringContainsString('data-detalle-privacidad hidden', $html);
        $this->assertStringContainsString('checkout-payment-modal__pci', $html);

        // Sigue sin haber campos propios de tarjeta: el QR no abre esa puerta.
        $this->assertDoesNotMatchRegularExpression(
            '/<input[^>]+(?:name|id)="[^"]*(?:cvv|vencimiento|numero[_-]?tarjeta)/i',
            $html
        );
    }

    /**
     * El QR de tarjeta es el enlace real del intento: la petición crea un solo
     * pedido, liga el intento y devuelve el código que se pinta en la ventana.
     * Una segunda llamada no puede duplicar la compra ni el cheque.
     */
    public function test_the_card_qr_creates_one_order_and_returns_the_gateway_link(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela();
        $this->simularPreferencia();

        $respuesta = $this->actingAs($usuario)->postJson(
            route('web.pedidos.pago_manual'),
            $this->datosCheckout('tarjeta') + ['qr_tarjeta' => '1']
        );

        $orden = Orden::sole();
        $intento = IntentoPagoMercadoPago::sole();

        $respuesta->assertOk()
            ->assertJsonPath('orden_id', $orden->id)
            ->assertJsonPath('checkout_url', self::SANDBOX_URL)
            ->assertJsonPath('confirmacion_url', route('web.pedidos.confirmacion', $orden->id))
            ->assertJsonStructure(['orden_id', 'total', 'checkout_url', 'qr', 'confirmacion_url', 'expira_at']);

        $datos = $respuesta->json();

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $datos['qr']);
        $this->assertSame($orden->id, $intento->pedido_id);
        $this->assertSame(IntentoPagoMercadoPago::ESTADO_ESPERANDO, $intento->estado);
        $this->assertSame('Pendiente', $orden->estado_pago);
        $this->assertSame(0, DB::table('carrito')->count());
        $this->assertDatabaseCount('pedido_pagos_manuales', 0);

        // El QR no cobra la tarjeta: solo abre el enlace del pedido.
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/v1/payments'));

        // Reintentar el paso con el carrito ya consumido no crea otro pedido ni
        // otro intento: el QR que se muestra sigue siendo el mismo cheque.
        $this->actingAs($usuario)->postJson(
            route('web.pedidos.pago_manual'),
            $this->datosCheckout('tarjeta') + ['qr_tarjeta' => '1']
        )->assertRedirect(route('web.carrito.index'));

        $this->assertSame(1, Orden::count());
        $this->assertSame(1, IntentoPagoMercadoPago::count());
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

    public function test_a_local_url_base_opens_the_checkout_without_return_urls(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela(urlBase: 'http://127.0.0.1:8000');
        $this->simularPreferencia();

        $this->actingAs($usuario)
            ->post(route('web.pedidos.pago_manual'), $this->datosCheckout('tarjeta'))
            ->assertRedirect(self::SANDBOX_URL);

        // Mercado Pago devuelve 400 «invalid_auto_return» si se le envía una URL de
        // retorno local: el checkout del cliente se abriría solo cuando la tienda ya
        // tenga dominio público.
        Http::assertSent(function ($request) {
            if ($request->url() !== 'https://api.mercadopago.com/checkout/preferences') {
                return true;
            }

            $datos = json_decode($request->body(), true);

            return ! array_key_exists('back_urls', $datos)
                && ! array_key_exists('auto_return', $datos)
                && ! array_key_exists('notification_url', $datos);
        });
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

    /**
     * Mercado Pago redirige a la URL de `failure` cuando el cobro no pasó. Hasta
     * aquí eso se ignoraba: el intento se quedaba «esperando pago» y el panel lo
     * mostraba como un cliente a punto de pagar.
     */
    public function test_a_declined_return_closes_the_attempt_without_touching_the_payment(): void
    {
        [$orden, $intento] = $this->crearPedidoConTarjeta();
        $usuario = User::where('galy_user_id', 42)->sole();

        Http::fake([
            'api.mercadopago.com/v1/payments/search*' => Http::response(['results' => []]),
        ]);

        $respuesta = $this->actingAs($usuario)
            ->get(route('web.pagos.tarjeta.retorno', [
                'referencia' => $intento->referencia_externa,
                'estado' => 'failure',
            ]));

        $respuesta->assertRedirect(route('web.pedidos.confirmacion', $orden->id))
            ->assertSessionHas('mensaje', 'Tu tarjeta fue rechazada y no se te cobró nada. Puedes reintentar el pago o elegir otro método.')
            ->assertSessionHas('icono', 'warning');

        $this->assertSame(IntentoPagoMercadoPago::ESTADO_FALLIDO, $intento->fresh()->estado);
        $this->assertNotSame('Pagado', $orden->fresh()->estado_pago);
        $this->assertNull($orden->fresh()->transaccion_id);
    }

    public function test_an_abandoned_return_leaves_the_attempt_waiting_because_nothing_declined_it(): void
    {
        [, $intento] = $this->crearPedidoConTarjeta();
        $usuario = User::where('galy_user_id', 42)->sole();

        Http::fake([
            'api.mercadopago.com/v1/payments/search*' => Http::response(['results' => []]),
        ]);

        $this->actingAs($usuario)
            ->get(route('web.pagos.tarjeta.retorno', ['referencia' => $intento->referencia_externa]))
            ->assertSessionHas('icono', 'info');

        $this->assertSame(IntentoPagoMercadoPago::ESTADO_ESPERANDO, $intento->fresh()->estado);
    }

    public function test_a_late_declined_return_never_reopens_a_confirmed_payment(): void
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
            ->get(route('web.pagos.tarjeta.retorno', ['referencia' => $intento->referencia_externa]));

        Http::fake([
            'api.mercadopago.com/v1/payments/search*' => Http::response(['results' => []]),
        ]);

        $this->actingAs($usuario)
            ->get(route('web.pagos.tarjeta.retorno', [
                'referencia' => $intento->referencia_externa,
                'estado' => 'failure',
            ]))
            ->assertSessionHas('mensaje', 'Mercado Pago confirmó tu pago. El pedido ya está en proceso.');

        $this->assertSame(IntentoPagoMercadoPago::ESTADO_CONFIRMADO, $intento->fresh()->estado);
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

    /**
     * El QR es el mismo cheque ya abierto, no una pasarela nueva: por eso se pinta
     * solo mientras el intento sigue vigente. Un QR de un enlace vencido invitaría
     * a pagar un cheque que la tienda ya dio por perdido.
     */
    public function test_la_confirmacion_de_la_tarjeta_da_un_qr_del_mismo_pago_para_el_celular(): void
    {
        [$orden, $intento] = $this->crearPedidoConTarjeta();
        $usuario = User::where('galy_user_id', 42)->sole();

        $this->actingAs($usuario)
            ->get(route('web.pedidos.confirmacion', $orden->id))
            ->assertOk()
            ->assertSee('¿Prefieres pagar desde tu celular?')
            ->assertSee('data:image/svg+xml;base64,', false)
            ->assertSee('Copiar enlace')
            ->assertSee('data-copy-payment-number="'.$intento->checkout_url.'"', false)
            ->assertSee('Vence a las');

        $intento->forceFill(['expira_at' => now()->subMinute()])->save();

        $this->actingAs($usuario)
            ->get(route('web.pedidos.confirmacion', $orden->id))
            ->assertOk()
            ->assertDontSee('¿Prefieres pagar desde tu celular?')
            ->assertSee('Pagar ahora con tarjeta');
    }

    /**
     * El QR no puede existir antes del pedido: un código pagable sin compra dejaría
     * un cobro de Mercado Pago sin pedido asociado. Este botón crea el pedido y
     * devuelve su ficha, en lugar de abrir la pasarela en esta pestaña.
     */
    public function test_pagar_desde_el_celular_guarda_el_pedido_y_devuelve_la_ficha_con_el_qr(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela();
        $this->simularPreferencia();

        $respuesta = $this->actingAs($usuario)
            ->post(route('web.pedidos.pago_manual'), $this->datosCheckout('tarjeta') + ['pago_celular' => '1']);

        $orden = Orden::sole();
        $intento = IntentoPagoMercadoPago::sole();

        $respuesta->assertRedirect(route('web.pedidos.confirmacion', $orden->id))
            ->assertSessionHas('mensaje', 'Pedido creado. Escanea el QR de esta pantalla con tu celular para pagar con tarjeta.');

        $this->assertSame($orden->id, $intento->pedido_id);
        $this->assertSame(IntentoPagoMercadoPago::ESTADO_ESPERANDO, $intento->estado);
        $this->assertSame('Pendiente', $orden->estado_pago);

        $this->actingAs($usuario)
            ->get(route('web.pedidos.confirmacion', $orden->id))
            ->assertOk()
            ->assertSee('¿Prefieres pagar desde tu celular?')
            ->assertSee('data-copy-payment-number="'.$intento->checkout_url.'"', false);
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

    // ////////////////////////////// CHECKOUT BRICKS (tarjeta dentro de la tienda) //////////////////////////////

    private const LLAVE_PUBLICA_DE_PRUEBA = 'TEST-8f4a1c2b-3d4e-4f50-9a1b-2c3d4e5f6a7b';

    private function encenderBricks(): void
    {
        config([
            'services.mercadopago.bricks' => true,
            'services.mercadopago.public_key' => self::LLAVE_PUBLICA_DE_PRUEBA,
        ]);
    }

    public function test_el_formulario_de_tarjeta_no_aparece_mientras_el_brick_esta_apagado(): void
    {
        $this->encenderBricks();
        [$orden] = $this->crearPedidoConTarjeta();

        $casos = [
            'bandera apagada' => ['bricks' => false, 'public_key' => self::LLAVE_PUBLICA_DE_PRUEBA],
            'sin public key' => ['bricks' => true, 'public_key' => null],
            'llave de otro ambiente' => ['bricks' => true, 'public_key' => 'APP_USR-8f4a1c2b-3d4e-4f50-9a1b-2c3d4e5f6a7b'],
            // El Access Token y la Public Key comparten forma; pegar el token en
            // el campo de la Public Key dejaba el formulario vacío y sin botón.
            'public key igual al access token' => ['bricks' => true, 'public_key' => 'TEST-8514936306504285-091415-3733c7f9ab12'],
        ];

        foreach ($casos as $caso => $config) {
            config(['services.mercadopago.bricks' => $config['bricks'], 'services.mercadopago.public_key' => $config['public_key']]);

            $html = $this->get(route('web.pedidos.confirmacion', $orden->id))
                ->assertOk()
                ->getContent();

            $this->assertStringNotContainsString('sdk.mercadopago.com', $html, $caso.' no debe cargar el SDK');
            $this->assertStringNotContainsString('cardPaymentBrick_container', $html, $caso.' no debe pintar el formulario');
            $this->assertStringContainsString('Pagar ahora con tarjeta', $html, $caso.' debe dejar el botón de siempre');
        }
    }

    public function test_la_confirmacion_muestra_el_formulario_de_mercado_pago_dentro_de_la_tienda(): void
    {
        $this->encenderBricks();
        [$orden] = $this->crearPedidoConTarjeta();

        $html = $this->get(route('web.pedidos.confirmacion', $orden->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('https://sdk.mercadopago.com/js/v2', $html);
        $this->assertStringContainsString('id="cardPaymentBrick_container"', $html);
        $this->assertStringContainsString(self::LLAVE_PUBLICA_DE_PRUEBA, $html);
        // Sin `onReady` el brick responde «Callbacks onReady and/or onError are
        // required» y no pinta el formulario.
        $this->assertStringContainsString('onReady:', $html);
        // El Access Token es secreto de servidor: si se cuela en el HTML, cualquier
        // visitante puede cobrar y reembolsar en la cuenta de la tienda.
        $this->assertStringNotContainsString('TEST-8514936306504285', $html);
        $this->assertStringContainsString('/pago-tarjeta-brick', $html);
        $this->assertStringContainsString('amount: 550', $html);

        // El número lo escribe Mercado Pago dentro de su iframe. Un campo propio de
        // tarjeta convertiría la tienda en alcance PCI y en un botín guardado.
        $this->assertDoesNotMatchRegularExpression(
            '/<input[^>]+(?:name|id)="[^"]*(?:cvv|vencimiento|numero[_-]?tarjeta|tarjeta[_-]?(?:numero|mes|a[nñ]o))"/i',
            $html
        );

        // El camino de siempre sigue intacto por si el SDK no carga.
        $this->assertStringContainsString('Prefiero pagar en la página de Mercado Pago', $html);
        // El guion del brick saca el token CSRF de ese formulario: sin ese id el
        // envío nunca sale y el cliente ve un formulario que no responde.
        $this->assertStringContainsString('id="brick-pago-alternativo"', $html);
    }

    public function test_el_pedido_de_tarjeta_se_qeda_en_la_tienda_cuando_el_brick_esta_listo(): void
    {
        $this->encenderBricks();
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela();
        $this->simularPreferencia();

        $this->actingAs($usuario)
            ->post(route('web.pedidos.pago_manual'), $this->datosCheckout('tarjeta'))
            ->assertRedirect(route('web.pedidos.confirmacion', Orden::sole()->id));

        $this->assertSame('Pendiente', Orden::sole()->estado_pago);
        // El enlace de Mercado Pago se conserva: es el botón alternativo y el QR del
        // celular. Apagar el brick no lo deja sin camino.
        $this->assertSame(self::SANDBOX_URL, IntentoPagoMercadoPago::sole()->checkout_url);
    }

    public function test_el_formulario_del_carrito_cobra_al_crear_el_pedido(): void
    {
        $this->encenderBricks();
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela();

        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref-carrito-brick',
                'init_point' => 'https://www.mercadopago.com/checkout/produccion',
                'sandbox_init_point' => self::SANDBOX_URL,
            ]),
            'api.mercadopago.com/v1/payments' => function ($request) {
                $datos = json_decode($request->body(), true);

                return Http::response([
                    'id' => 9100,
                    'status' => 'approved',
                    'status_detail' => 'accredited',
                    'transaction_amount' => $datos['transaction_amount'],
                    'currency_id' => 'PEN',
                    'external_reference' => $datos['external_reference'],
                ]);
            },
        ]);

        $this->actingAs($usuario)
            ->post(route('web.pedidos.pago_manual'), array_merge($this->datosCheckout('tarjeta'), [
                'brick_token' => 'token-de-la-ventana-de-pago',
                'brick_payment_method_id' => 'visa',
                'brick_installments' => 1,
                'brick_email' => 'cliente@example.com',
            ]))
            ->assertRedirect(route('web.pedidos.confirmacion', Orden::sole()->id));

        $this->assertSame('Pagado', Orden::sole()->estado_pago);
        $this->assertSame(IntentoPagoMercadoPago::ESTADO_CONFIRMADO, IntentoPagoMercadoPago::sole()->estado);

        Http::assertSent(function ($request) {
            if ($request->url() !== 'https://api.mercadopago.com/v1/payments') {
                return true;
            }

            $datos = json_decode($request->body(), true);

            // El monto lo pone la tienda: el navegador no puede bajarlo.
            return $datos['token'] === 'token-de-la-ventana-de-pago'
                && (float) $datos['transaction_amount'] === 550.0;
        });
    }

    public function test_un_token_del_brick_no_cobra_si_la_bandera_esta_apagada(): void
    {
        config(['services.mercadopago.bricks' => false, 'services.mercadopago.public_key' => null]);
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela();
        $this->simularPreferencia();

        $this->actingAs($usuario)
            ->post(route('web.pedidos.pago_manual'), array_merge($this->datosCheckout('tarjeta'), [
                'brick_token' => 'token-de-un-brick-apagado',
                'brick_payment_method_id' => 'visa',
            ]))
            ->assertRedirect(self::SANDBOX_URL);

        $this->assertSame('Pendiente', Orden::sole()->estado_pago);
        $this->assertSame(IntentoPagoMercadoPago::ESTADO_ESPERANDO, IntentoPagoMercadoPago::sole()->estado);
    }

    public function test_el_modal_promete_la_tienda_y_no_la_salida_cuando_el_brick_esta_listo(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela();
        $this->encenderBricks();

        $html = $this->actingAs($usuario)
            ->get(route('web.carrito.index'))
            ->assertOk()
            ->getContent();

        // El formulario real se pinta dentro de la ventana de pago, no en «la
        // pantalla siguiente»: la promesa tiene que decir la verdad.
        $this->assertStringContainsString('id="cardPaymentBrickModal"', $html);
        $this->assertStringContainsString('https://sdk.mercadopago.com/js/v2', $html);
        $this->assertStringContainsString(self::LLAVE_PUBLICA_DE_PRUEBA, $html);
        // El CardPayment Brick rechaza el formulario si falta `onReady`.
        $this->assertStringContainsString('onReady:', $html);
        $this->assertStringContainsString('Escribe tu tarjeta aquí mismo', $html);
        $this->assertStringNotContainsString('En la pantalla siguiente Mercado Pago', $html);
        $this->assertStringNotContainsString('checkout-paycard__preview', $html);

        // El Access Token es secreto de servidor: si se cuela en el HTML,
        // cualquier visitante puede cobrar y reembolsar en la cuenta.
        $this->assertStringNotContainsString('TEST-8514936306504285', $html);

        // El brick no crea campos propios de tarjeta: el número lo escribe
        // Mercado Pago dentro de su iframe.
        $this->assertDoesNotMatchRegularExpression(
            '/<input[^>]+(?:name|id)="[^"]*(?:cvv|vencimiento|numero[_-]?tarjeta|tarjeta[_-]?(?:numero|mes|a[nñ]o))"/i',
            $html
        );
    }

    public function test_el_carrito_no_carga_el_sdk_si_la_public_key_es_el_token(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela();
        $this->encenderBricks();
        config(['services.mercadopago.public_key' => config('services.mercadopago.access_token')]);

        $mercadoPago = app(MercadoPagoService::class);

        $this->assertFalse($mercadoPago->llavePublicaDelMismoAmbiente());
        $this->assertFalse($mercadoPago->brickListo());
        $this->assertStringContainsString('Access Token', (string) $mercadoPago->motivoBrickApagado());

        $html = $this->actingAs($usuario)
            ->get(route('web.carrito.index'))
            ->assertOk()
            ->getContent();

        // Sin Public Key real no se pinta un formulario que Mercado Pago va a
        // rechazar: la ventana de pago deja el botón que abre su checkout.
        $this->assertStringNotContainsString('id="cardPaymentBrickModal"', $html);
        $this->assertStringNotContainsString('https://sdk.mercadopago.com/js/v2', $html);
    }

    public function test_el_brick_cobra_el_monto_del_pedido_no_el_que_envia_el_navegador(): void
    {
        $this->encenderBricks();
        [$orden, $intento] = $this->crearPedidoConTarjeta();

        Http::fake([
            'api.mercadopago.com/v1/payments' => Http::response([
                'id' => 9001,
                'status' => 'approved',
                'status_detail' => 'accredited',
                'transaction_amount' => 550,
                'currency_id' => 'PEN',
                'external_reference' => $intento->referencia_externa,
            ]),
        ]);

        $this->postJson(route('web.pedidos.tarjeta.brick', $orden->id), [
            'token' => 'token-de-tarjeta-del-brick',
            'payment_method_id' => 'visa',
            'installments' => 3,
            'email' => 'cliente@example.com',
            'transaction_amount' => 0.01,
            'external_reference' => 'otra-referencia',
        ])
            ->assertOk()
            ->assertJson(['resultado' => 'confirmado']);

        Http::assertSent(function ($request) use ($intento) {
            if ($request->url() !== 'https://api.mercadopago.com/v1/payments') {
                return true;
            }

            $datos = json_decode($request->body(), true);

            return (float) $datos['transaction_amount'] === 550.0
                && $datos['external_reference'] === $intento->referencia_externa
                && $datos['installments'] === 3
                && $datos['payment_method_id'] === 'visa'
                && $request->hasHeader('X-Idempotency-Key');
        });

        $this->assertSame('Pagado', Orden::sole()->estado_pago);
        $this->assertSame(IntentoPagoMercadoPago::ESTADO_CONFIRMADO, IntentoPagoMercadoPago::sole()->estado);
    }

    public function test_una_tarjeta_rechazada_deja_el_pedido_pendiente_y_sin_cobro(): void
    {
        $this->encenderBricks();
        [$orden, $intento] = $this->crearPedidoConTarjeta();

        Http::fake([
            'api.mercadopago.com/v1/payments' => Http::response([
                'id' => 9002,
                'status' => 'rejected',
                'status_detail' => 'cc_rejected_insufficient_amount',
                'transaction_amount' => 550,
                'currency_id' => 'PEN',
                'external_reference' => $intento->referencia_externa,
            ], 400),
        ]);

        $this->postJson(route('web.pedidos.tarjeta.brick', $orden->id), [
            'token' => 'token-de-tarjeta-rechazada',
            'payment_method_id' => 'visa',
        ])
            ->assertOk()
            ->assertJson(['resultado' => 'rechazado', 'mensaje' => 'Tu tarjeta fue rechazada y no se te cobró nada. Puedes probar con otra tarjeta.']);

        $this->assertSame('Pendiente', Orden::sole()->estado_pago);
        $this->assertSame(IntentoPagoMercadoPago::ESTADO_FALLIDO, IntentoPagoMercadoPago::sole()->estado);
        $this->assertSame('cc_rejected_insufficient_amount', IntentoPagoMercadoPago::sole()->detalle_estado_mercadopago);

        // Regresión: la base GALY declara `estado_nuevo` como NOT NULL. Un
        // rechazo no cambia la etapa del pedido, pero la nota del historial
        // igual necesita un valor: escribiendo null el INSERT explotaba y el
        // cliente veía un 500 justo después de pagar.
        $historial = \App\Models\PedidoHistorial::where('orden_id', $orden->id)
            ->where('nota', 'like', '%rejected%')
            ->sole();

        $this->assertNotNull($historial->estado_nuevo);
        $this->assertSame($orden->estado_orden, $historial->estado_nuevo);
        $this->assertStringContainsString('rejected', $historial->nota);
    }

    public function test_un_rechazo_sin_pago_en_el_cuerpo_se_lee_de_la_referencia(): void
    {
        $this->encenderBricks();
        [$orden, $intento] = $this->crearPedidoConTarjeta();

        Http::fake([
            'api.mercadopago.com/v1/payments/search*' => Http::response(['results' => [[
                'id' => 9003,
                'status' => 'rejected',
                'status_detail' => 'cc_rejected_call_for_authorize',
                'transaction_amount' => 550,
                'currency_id' => 'PEN',
                'external_reference' => $intento->referencia_externa,
            ]]]),
            'api.mercadopago.com/v1/payments' => Http::response(['message' => 'The payment was rejected', 'status' => 400], 400),
        ]);

        $this->postJson(route('web.pedidos.tarjeta.brick', $orden->id), [
            'token' => 'token-de-tarjeta-rechazada',
            'payment_method_id' => 'master',
        ])
            ->assertOk()
            ->assertJson(['resultado' => 'rechazado']);

        $this->assertSame('Pendiente', Orden::sole()->estado_pago);
        $this->assertSame('9003', IntentoPagoMercadoPago::sole()->mercadopago_payment_id);
    }

    /**
     * El 500 del 9/11/2026: el POST se cortó por tiempo y el cliente vio un
     * error aunque Mercado Pago sí había aprobado. Antes de afirmar «no se
     * cobró» la tienda pregunta por la referencia y aplica el cobro.
     */
    public function test_un_fallo_de_red_al_cobrar_confirma_el_pago_que_mercado_pago_si_registro(): void
    {
        $this->encenderBricks();
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela();

        $cobrosEnviados = 0;

        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref-fallo-de-red',
                'init_point' => 'https://www.mercadopago.com/checkout/produccion',
                'sandbox_init_point' => self::SANDBOX_URL,
            ]),
            'api.mercadopago.com/v1/payments/search*' => fn () => Http::response(['results' => [[
                'id' => 9101,
                'status' => 'approved',
                'status_detail' => 'accredited',
                'transaction_amount' => 550,
                'currency_id' => 'PEN',
                'external_reference' => IntentoPagoMercadoPago::sole()->referencia_externa,
            ]]]),
            'api.mercadopago.com/v1/payments' => function () use (&$cobrosEnviados): void {
                $cobrosEnviados++;

                throw new ConnectionException('cURL error 28: Operation timed out after 12001 milliseconds');
            },
        ]);

        $this->actingAs($usuario)
            ->post(route('web.pedidos.pago_manual'), array_merge($this->datosCheckout('tarjeta'), [
                'brick_token' => 'token-que-si-llego',
                'brick_payment_method_id' => 'visa',
                'brick_installments' => 1,
                'brick_email' => 'cliente@example.com',
            ]))
            ->assertRedirect(route('web.pedidos.confirmacion', Orden::sole()->id))
            ->assertSessionHas('mensaje', 'Mercado Pago confirmó tu pago. Tu pedido ya está en proceso.');

        $this->assertSame('Pagado', Orden::sole()->estado_pago);
        $this->assertSame(IntentoPagoMercadoPago::ESTADO_CONFIRMADO, IntentoPagoMercadoPago::sole()->estado);

        // Un timeout no se reintenta con otro POST: dos cobros serían dos cargos.
        $this->assertSame(1, $cobrosEnviados);
    }

    public function test_un_fallo_de_red_sin_cobro_avisa_al_cliente_sin_dejar_el_pago_en_duda(): void
    {
        $this->encenderBricks();
        $usuario = $this->crearClienteConCarrito();
        $this->configurarPasarela();

        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref-sin-red',
                'init_point' => 'https://www.mercadopago.com/checkout/produccion',
                'sandbox_init_point' => self::SANDBOX_URL,
            ]),
            'api.mercadopago.com/v1/payments/search*' => Http::response(['results' => []]),
            'api.mercadopago.com/v1/payments' => function (): void {
                throw new ConnectionException('cURL error 6: Could not resolve host');
            },
        ]);

        $this->actingAs($usuario)
            ->post(route('web.pedidos.pago_manual'), array_merge($this->datosCheckout('tarjeta'), [
                'brick_token' => 'token-sin-respuesta',
                'brick_payment_method_id' => 'visa',
                'brick_installments' => 1,
            ]))
            ->assertRedirect(route('web.pedidos.confirmacion', Orden::sole()->id))
            ->assertSessionHas('mensaje', 'No pudimos comunicarnos con Mercado Pago y no se registró ningún cobro. Puedes intentarlo otra vez en esta pantalla.');

        $this->assertSame('Pendiente', Orden::sole()->estado_pago);
        $this->assertSame(IntentoPagoMercadoPago::ESTADO_ESPERANDO, IntentoPagoMercadoPago::sole()->estado);
    }

    public function test_el_formulario_de_la_confirmacion_confirma_un_cobro_cuya_respuesta_se_perdio(): void
    {
        $this->encenderBricks();
        [$orden, $intento] = $this->crearPedidoConTarjeta();

        Http::fake([
            'api.mercadopago.com/v1/payments/search*' => Http::response(['results' => [[
                'id' => 9102,
                'status' => 'approved',
                'status_detail' => 'accredited',
                'transaction_amount' => 550,
                'currency_id' => 'PEN',
                'external_reference' => $intento->referencia_externa,
            ]]]),
            'api.mercadopago.com/v1/payments' => function (): void {
                throw new ConnectionException('cURL error 28: Operation timed out');
            },
        ]);

        $this->postJson(route('web.pedidos.tarjeta.brick', $orden->id), [
            'token' => 'token-perdido-en-la-red',
            'payment_method_id' => 'visa',
        ])
            ->assertOk()
            ->assertJson([
                'resultado' => 'confirmado',
                'mensaje' => 'Mercado Pago confirmó tu pago. El pedido ya está en proceso.',
            ]);

        $this->assertSame('Pagado', Orden::sole()->estado_pago);
        $this->assertSame(IntentoPagoMercadoPago::ESTADO_CONFIRMADO, IntentoPagoMercadoPago::sole()->estado);
    }

    public function test_el_formulario_de_la_confirmacion_responde_502_si_tampoco_puede_verificar(): void
    {
        $this->encenderBricks();
        [$orden] = $this->crearPedidoConTarjeta();

        Http::fake([
            'api.mercadopago.com/v1/payments/search*' => function (): void {
                throw new ConnectionException('sin salida a internet');
            },
            'api.mercadopago.com/v1/payments' => function (): void {
                throw new ConnectionException('sin salida a internet');
            },
        ]);

        $this->postJson(route('web.pedidos.tarjeta.brick', $orden->id), [
            'token' => 'token-sin-red',
            'payment_method_id' => 'visa',
        ])
            ->assertStatus(502)
            ->assertJson([
                'resultado' => 'error',
                'mensaje' => 'No pudimos comunicarnos con Mercado Pago y no se registró ningún cobro. Inténtalo de nuevo en unos minutos.',
            ]);

        $this->assertSame('Pendiente', Orden::sole()->estado_pago);
        $this->assertSame(IntentoPagoMercadoPago::ESTADO_ESPERANDO, IntentoPagoMercadoPago::sole()->estado);
    }

    public function test_el_brick_no_deja_repetir_un_cobro_que_mercado_pago_ya_aprobo(): void
    {
        $this->encenderBricks();
        [$orden, $intento] = $this->crearPedidoConTarjeta();

        $intento->estado_mercadopago = 'approved';
        $intento->save();

        $this->postJson(route('web.pedidos.tarjeta.brick', $orden->id), [
            'token' => 'otro-token',
            'payment_method_id' => 'visa',
        ])
            ->assertStatus(409)
            ->assertJson(['resultado' => 'pendiente']);

        Http::assertNotSent(fn ($request) => $request->url() === 'https://api.mercadopago.com/v1/payments');

        $this->assertSame('Pendiente', Orden::sole()->estado_pago);
    }

    public function test_otro_cliente_no_puede_cobrar_un_pedido_ajeno_desde_el_brick(): void
    {
        $this->encenderBricks();
        [$orden] = $this->crearPedidoConTarjeta();

        $ajeno = User::factory()->create(['galy_user_id' => 77, 'email_verified_at' => now()]);

        $this->actingAs($ajeno)
            ->postJson(route('web.pedidos.tarjeta.brick', $orden->id), [
                'token' => 'token-de-otro',
                'payment_method_id' => 'visa',
            ])
            ->assertNotFound();

        Http::assertNotSent(fn ($request) => $request->url() === 'https://api.mercadopago.com/v1/payments');
    }

    public function test_una_credencial_rechazada_apaga_el_brick_como_apaga_la_tarjeta(): void
    {
        $this->encenderBricks();
        [$orden] = $this->crearPedidoConTarjeta();

        Http::fake([
            'api.mercadopago.com/v1/payments' => Http::response(['message' => 'forbidden'], 403),
        ]);

        $this->postJson(route('web.pedidos.tarjeta.brick', $orden->id), [
            'token' => 'token-cualquiera',
            'payment_method_id' => 'visa',
        ])->assertStatus(502);

        $mercadoPago = app(MercadoPagoService::class);
        $this->assertFalse($mercadoPago->brickListo());
        $this->assertSame('Pendiente', Orden::sole()->estado_pago);
    }

    public function test_sin_token_el_brick_no_inventa_un_cobro(): void
    {
        $this->encenderBricks();
        [$orden] = $this->crearPedidoConTarjeta();

        $this->postJson(route('web.pedidos.tarjeta.brick', $orden->id), [
            'payment_method_id' => 'visa',
        ])->assertStatus(422);

        Http::assertNotSent(fn ($request) => $request->url() === 'https://api.mercadopago.com/v1/payments');
        $this->assertSame('Pendiente', Orden::sole()->estado_pago);
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
