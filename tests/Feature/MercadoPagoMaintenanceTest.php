<?php

namespace Tests\Feature;

use App\Models\IntentoPagoMercadoPago;
use App\Services\MercadoPagoService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\CreatesOrderPaymentTables;
use Tests\TestCase;

class MercadoPagoMaintenanceTest extends TestCase
{
    use CreatesOrderPaymentTables;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearTablaIntentosMercadoPago();

        config([
            'services.mercadopago.enabled' => true,
            'services.mercadopago.access_token' => 'TEST-8514936306504285-091415-3733c7f9ab12',
            'services.mercadopago.webhook_secret' => null,
            'services.mercadopago.url_base' => 'https://tienda.example',
        ]);
    }

    private function crearIntento(string $estado, string $expiraAt): string
    {
        $referencia = (string) Str::uuid();

        DB::table('intentos_pago_mercadopago')->insert([
            'referencia_externa' => $referencia,
            'usuario_id' => 42,
            'firma_carrito' => hash('sha256', $referencia),
            'monto' => 129.90,
            'divisa' => 'PEN',
            'estado' => $estado,
            'mercadopago_order_id' => 'order-'.$referencia,
            'direccion_envio' => 'Tienda GALY',
            'modalidad_entrega' => 'recojo',
            'items' => json_encode([['nombre' => 'Producto', 'cantidad' => 1]]),
            'expira_at' => $expiraAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $referencia;
    }

    /**
     * La pausa solo tiene sentido sobre una tarjeta que estaba en la vitrina:
     * sin esa huella en la base, un rechazo no tiene nada que retirar.
     */
    private function credencialEnVitrina(): void
    {
        if (DB::table('ajustes')->count() === 0) {
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
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        app(MercadoPagoService::class)->registrarVerificacion();
    }

    public function test_the_doctor_command_flags_a_temporary_tunnel_as_a_problem_for_the_customer(): void
    {
        config([
            'services.mercadopago.url_base' => null,
            'services.mercadopago.access_token' => null,
            'app.url' => 'https://nombre-aleatorio.trycloudflare.com',
        ]);

        Artisan::call('mercadopago:verificar');
        $salida = Artisan::output();

        $this->assertStringContainsString('túnel temporal', $salida);
        $this->assertStringContainsString('nombre-aleatorio.trycloudflare.com', $salida);
    }

    public function test_the_sweeper_only_expires_card_attempts_that_waited_past_their_deadline(): void
    {
        $vencido = $this->crearIntento(IntentoPagoMercadoPago::ESTADO_ESPERANDO, now()->subMinutes(5)->toDateTimeString());
        $vigente = $this->crearIntento(IntentoPagoMercadoPago::ESTADO_ESPERANDO, now()->addMinutes(25)->toDateTimeString());
        $rechazado = $this->crearIntento(IntentoPagoMercadoPago::ESTADO_FALLIDO, now()->subHour()->toDateTimeString());

        $this->artisan('mercadopago:expirar')
            ->expectsOutput('Intentos de pago con tarjeta expirados: 1')
            ->assertSuccessful();

        $this->assertSame(IntentoPagoMercadoPago::ESTADO_EXPIRADO, IntentoPagoMercadoPago::find($vencido)->estado);
        $this->assertSame(IntentoPagoMercadoPago::ESTADO_ESPERANDO, IntentoPagoMercadoPago::find($vigente)->estado);
        $this->assertSame(IntentoPagoMercadoPago::ESTADO_FALLIDO, IntentoPagoMercadoPago::find($rechazado)->estado);
    }

    public function test_the_sweeper_runs_on_the_scheduler_so_the_admin_panel_stays_accurate(): void
    {
        $eventos = collect(app(Schedule::class)->events())
            ->filter(fn ($event) => str_contains((string) ($event->command ?? ''), 'mercadopago:expirar')
                || str_contains((string) ($event->description ?? ''), 'mercadopago:expirar'));

        $this->assertCount(1, $eventos);
        $this->assertSame('*/15 * * * *', $eventos->first()->getExpression());
    }

    public function test_the_reconciler_runs_on_the_scheduler_so_a_lost_webhook_is_recovered(): void
    {
        $eventos = collect(app(Schedule::class)->events())
            ->filter(fn ($event) => str_contains((string) ($event->command ?? ''), 'mercadopago:reconciliar')
                || str_contains((string) ($event->description ?? ''), 'mercadopago:reconciliar'));

        $this->assertCount(1, $eventos);
        $this->assertSame('*/10 * * * *', $eventos->first()->getExpression());
    }

    public function test_the_doctor_command_warns_about_charges_the_store_has_to_review(): void
    {
        $porAplicar = $this->crearIntento(IntentoPagoMercadoPago::ESTADO_ESPERANDO, now()->addMinutes(20)->toDateTimeString());
        $revertido = $this->crearIntento(IntentoPagoMercadoPago::ESTADO_FALLIDO, now()->subHour()->toDateTimeString());

        DB::table('intentos_pago_mercadopago')->where('referencia_externa', $porAplicar)
            ->update(['estado_mercadopago' => 'approved']);
        DB::table('intentos_pago_mercadopago')->where('referencia_externa', $revertido)
            ->update(['estado_mercadopago' => 'charged_back']);

        Artisan::call('mercadopago:verificar');
        $salida = Artisan::output();

        $this->assertStringContainsString('1 cobro(s) aprobado(s) sin aplicar', $salida);
        $this->assertStringContainsString('1 cobro(s) revertido(s)', $salida);
        $this->assertStringContainsString('mercadopago:reconciliar', $salida);
    }

    public function test_the_doctor_command_explains_what_is_missing_without_revealing_the_token(): void
    {
        config(['services.mercadopago.enabled' => false]);

        Artisan::call('mercadopago:verificar');
        $salida = Artisan::output();

        $this->assertStringContainsString('MERCADOPAGO_ENABLED', $salida);
        $this->assertStringContainsString('de pruebas (TEST-), 41 caracteres', $salida);
        $this->assertStringContainsString('https://tienda.example', $salida);
        $this->assertStringContainsString('El pago con tarjeta NO se muestra todavía', $salida);
        $this->assertStringNotContainsString('3733c7f9ab12', $salida);
    }

    public function test_the_doctor_command_confirms_the_gateway_and_names_its_owner_when_asked(): void
    {
        Http::fake([
            'api.mercadopago.com/users/me' => Http::response(['id' => 987, 'email' => 'tienda@example.test']),
        ]);

        Artisan::call('mercadopago:verificar', ['--probar' => true]);
        $salida = Artisan::output();

        $this->assertStringContainsString('El token es válido y pertenece a tienda@example.test', $salida);
        $this->assertStringNotContainsString('3733c7f9ab12', $salida);
    }

    public function test_the_doctor_command_fails_loudly_when_mercado_pago_rejects_the_token(): void
    {
        Http::fake([
            'api.mercadopago.com/users/me' => Http::response(['message' => 'bad_token'], 401),
        ]);

        $this->credencialEnVitrina();

        $this->artisan('mercadopago:verificar', ['--probar' => true])
            ->expectsOutput('Mercado Pago no aceptó el token: HTTP 401 — bad_token')
            ->expectsOutput('Repetir la prueba con este mismo token da exactamente este resultado. Lo que falta es el Access Token de tu cuenta.')
            ->assertFailed();

        $this->assertTrue(app(MercadoPagoService::class)->rechazoReciente());
        // Un token rechazado no puede quedar "verificado" por mucho que sea de
        // pruebas: esa huella es justo lo que enciende la tarjeta en el carrito.
        $this->assertFalse(app(MercadoPagoService::class)->credencialVerificada());
    }

    /**
     * El dueño corre el diagnóstico con un token que nunca funcionó. Decirle
     * «Tarjeta en pausa» le hace creer que algo funcionaba y se rompió, cuando lo
     * único que hay es una credencial sin comprobar.
     */
    public function test_rejecting_a_credential_that_was_never_offered_does_not_claim_a_pause(): void
    {
        Http::fake([
            'api.mercadopago.com/users/me' => Http::response(['message' => 'bad_token'], 403),
        ]);

        $this->artisan('mercadopago:verificar', ['--probar' => true])->assertFailed();

        $this->assertFalse(app(MercadoPagoService::class)->rechazoReciente());

        Artisan::call('mercadopago:verificar');
        $this->assertStringNotContainsString('La opción está retirada', Artisan::output());
    }

    /**
     * Un token rechazado tiene que apagar la opción sin que nadie lo decida a
     * mano, y un token válido tiene que volver a encenderla al instante.
     */
    public function test_a_valid_token_from_the_doctor_command_reopens_the_card_option(): void
    {
        $this->credencialEnVitrina();
        app(MercadoPagoService::class)->bloquearPorRechazo();

        Http::fake([
            'api.mercadopago.com/users/me' => Http::response(['id' => 987, 'email' => 'tienda@example.test']),
        ]);

        $this->artisan('mercadopago:verificar', ['--probar' => true])->assertSuccessful();

        $this->assertFalse(app(MercadoPagoService::class)->rechazoReciente());
    }

    /**
     * La tabla se imprime antes de lanzar la prueba, así que describe el estado de
     * entrada. Sin un resumen de después, el dueño veía «NO» en pantalla justo
     * después de haber conseguido un sí y volvía a correr el mismo comando.
     */
    public function test_the_doctor_command_names_the_one_remaining_step_after_a_successful_probe(): void
    {
        $this->credencialEnVitrina();

        Http::fake([
            'api.mercadopago.com/users/me' => Http::response(['id' => 987, 'email' => 'tienda@example.test']),
        ]);

        Artisan::call('mercadopago:verificar', ['--probar' => true]);
        $salida = Artisan::output();

        $this->assertStringContainsString('Falta un clic', $salida);
        $this->assertStringContainsString('Ofrecer pago con tarjeta', $salida);

        DB::table('ajustes')->update(['web_mercadopago_activa' => 1]);

        Artisan::call('mercadopago:verificar', ['--probar' => true]);

        $this->assertStringContainsString('la tarjeta Visa se ofrece en el carrito', Artisan::output());
    }

    public function test_the_doctor_command_says_the_option_is_withdrawn_while_it_is_locked(): void
    {
        $this->credencialEnVitrina();
        app(MercadoPagoService::class)->bloquearPorRechazo();

        Artisan::call('mercadopago:verificar');
        $salida = Artisan::output();

        $this->assertStringContainsString('Mercado Pago rechazó la credencial', $salida);
        $this->assertStringContainsString('Vuelve sola media hora', $salida);
        $this->assertStringContainsString('no la reabre', $salida);
        $this->assertStringNotContainsString('ya mismo con', $salida);
    }

    /**
     * Prometer una espera de media hora cuando ya nada va a esperar a nadie hace
     * que el dueño siga probando la tarjeta en vez de arreglar la credencial.
     */
    public function test_the_doctor_command_stops_promising_a_wait_after_three_rejections(): void
    {
        $mercadoPago = app(MercadoPagoService::class);

        foreach (['aaaaaaaaaaaa', 'bbbbbbbbbbbb', 'cccccccccccc'] as $firma) {
            config(['services.mercadopago.access_token' => 'TEST-111111-2222-'.$firma]);
            $this->credencialEnVitrina();
            $mercadoPago->bloquearPorRechazo();
        }

        Artisan::call('mercadopago:verificar');
        $salida = Artisan::output();

        $this->assertStringContainsString('no va a volver sola', $salida);
    }

    /**
     * Un dueño que corre `--probar` dos veces seguidas con el mismo token mal no
     * está agotando la pasarela: está repitiendo un diagnóstico. Castigar eso con
     * un bloqueo definitivo le hace creer que empeoró algo que no tocó.
     */
    public function test_repeating_the_probe_with_the_same_token_never_escalates_the_lock(): void
    {
        config(['services.mercadopago.access_token' => 'TEST-111111-2222-aaaaaaaaaaaa']);
        $mercadoPago = app(MercadoPagoService::class);
        $this->credencialEnVitrina();

        $mercadoPago->bloquearPorRechazo();
        $mercadoPago->bloquearPorRechazo();
        $mercadoPago->bloquearPorRechazo();
        $mercadoPago->bloquearPorRechazo();

        $this->assertTrue($mercadoPago->rechazoReciente());
        $this->assertFalse($mercadoPago->bloqueoDefinitivo());
    }

    public function test_the_doctor_command_does_not_need_a_token_before_testing_it(): void
    {
        config(['services.mercadopago.access_token' => null]);

        $this->artisan('mercadopago:verificar', ['--probar' => true])
            ->expectsOutput('No hay token que probar.')
            ->assertFailed();
    }

    /**
     * Sin red el resultado es el mismo que con un token muerto, y las dos cosas
     * se arreglan en sitios distintos: el médico tiene que decirlas por nombre.
     */
    public function test_the_doctor_command_blames_the_connection_when_the_api_is_unreachable(): void
    {
        Http::fake(function (): void {
            throw new \Illuminate\Http\Client\ConnectionException('sin salida a internet');
        });

        $this->artisan('mercadopago:verificar', ['--probar' => true])
            ->expectsOutput('Mercado Pago no aceptó el token: no se pudo hablar con la API: sin salida a internet')
            ->assertFailed();
    }
}
