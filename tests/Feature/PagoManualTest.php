<?php

namespace Tests\Feature;

use App\Models\Ajuste;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PagoManualTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_transfer_is_rejected_until_receiver_number_and_qr_are_configured(): void
    {
        $usuario = User::factory()->create([
            'galy_user_id' => 42,
            'google_id' => 'google-42',
        ]);

        $this->actingAs($usuario)
            ->from(route('web.carrito.index'))
            ->post(route('web.pedidos.pago_manual'), [
                'name' => $usuario->name,
                'dni' => '12345678',
                'telefono' => '987654321',
                'privacy_consent' => '1',
                'direccion_id' => 1,
                'metodo_pago' => 'yape',
                'modalidad_entrega' => 'recojo',
            ])
            ->assertRedirect(route('web.carrito.index'))
            ->assertSessionHas('mensaje', 'Elige otro método de pago por ahora: la tienda aún no puede recibir transferencias por Yape.');
    }

    public function test_app_payment_settings_do_not_enable_the_web_checkout(): void
    {
        $usuario = User::factory()->create([
            'galy_user_id' => 42,
            'google_id' => 'google-42',
            'email_verified_at' => now(),
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
            'yape_numero' => '111111111',
            'yape_titular' => 'Titular app',
            'yape_qr' => 'pagos/qr/app/yape.png',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($usuario)
            ->from(route('web.carrito.index'))
            ->post(route('web.pedidos.pago_manual'), [
            'name' => $usuario->name,
            'dni' => '12345678',
            'telefono' => '987654321',
            'privacy_consent' => '1',
            'direccion_id' => 1,
            'metodo_pago' => 'yape',
            'modalidad_entrega' => 'recojo',
            ])
            ->assertRedirect(route('web.carrito.index'))
            ->assertSessionHas('mensaje', 'Elige otro método de pago por ahora: la tienda aún no puede recibir transferencias por Yape.');
    }

    public function test_manual_transfer_rejects_an_account_without_a_linked_galy_user(): void
    {
        $usuario = User::factory()->create(['google_id' => 'google-unlinked']);

        $this->actingAs($usuario)
            ->from(route('web.carrito.index'))
            ->post(route('web.pedidos.pago_manual'), [
                'name' => $usuario->name,
                'dni' => '12345678',
                'telefono' => '987654321',
                'privacy_consent' => '1',
                'direccion_id' => 1,
                'metodo_pago' => 'plin',
                'modalidad_entrega' => 'recojo',
            ])
            ->assertRedirect(route('web.carrito.index'))
            ->assertSessionHas('mensaje', 'No se encontró una cuenta GALY vinculada a este correo. Inicia sesión con tu cuenta GALY para compartir el carrito.');
    }

    public function test_manual_checkout_requires_personal_details_and_an_address_even_for_store_pickup(): void
    {
        $usuario = User::factory()->create(['galy_user_id' => 42]);

        $this->actingAs($usuario)
            ->from(route('web.carrito.index'))
            ->post(route('web.pedidos.pago_manual'), [
                'metodo_pago' => 'yape',
                'modalidad_entrega' => 'recojo',
            ])
            ->assertRedirect(route('web.carrito.index'))
            ->assertSessionHasErrors(['name', 'dni', 'telefono', 'privacy_consent', 'direccion_id']);
    }

    public function test_manual_checkout_rejects_invalid_dni_and_phone_numbers(): void
    {
        $usuario = User::factory()->create(['galy_user_id' => 42]);

        $this->actingAs($usuario)
            ->from(route('web.carrito.index'))
            ->post(route('web.pedidos.pago_manual'), [
                'name' => $usuario->name,
                'dni' => '1234ABCD',
                'telefono' => 'telefono',
                'privacy_consent' => '1',
                'direccion_id' => 1,
                'metodo_pago' => 'yape',
                'modalidad_entrega' => 'recojo',
            ])
            ->assertRedirect(route('web.carrito.index'))
            ->assertSessionHasErrors(['dni', 'telefono']);
    }

    public function test_manual_checkout_requires_a_google_verified_account_before_creating_an_order(): void
    {
        $usuario = User::factory()->create(['galy_user_id' => 42]);

        $this->actingAs($usuario)
            ->from(route('web.carrito.index'))
            ->post(route('web.pedidos.pago_manual'), [
                'name' => $usuario->name,
                'dni' => '12345678',
                'telefono' => '987654321',
                'privacy_consent' => '1',
                'direccion_id' => 1,
                'metodo_pago' => 'yape',
                'modalidad_entrega' => 'recojo',
            ])
            ->assertRedirect(route('web.login'))
            ->assertSessionHas('google_login_error', 'Verifica tu correo iniciando sesión con Google antes de pagar.');
    }

    public function test_customer_dni_is_encrypted_in_the_user_record(): void
    {
        $usuario = User::factory()->create();
        $usuario->forceFill(['dni' => '12345678'])->save();

        $storedDni = DB::table('users')->where('id', $usuario->id)->value('dni');

        $this->assertNotSame('12345678', $storedDni);
        $this->assertSame('12345678', $usuario->fresh()->dni);
    }

    public function test_the_destination_rules_only_accept_a_real_bank_account(): void
    {
        $this->assertNull(Ajuste::motivoDeDestinoPago('bcp', '00110002000300040055', 'TECTRON GALY EIRL'));
        $this->assertNull(Ajuste::motivoDeDestinoPago('bcp', '00220011223344556677', 'TECTRON GALY EIRL'));
        $this->assertNull(Ajuste::motivoDeDestinoPago('yape', '987654321', 'Galy Tectron', 'pagos/qr/yape.png'));
        $this->assertStringContainsString('20 dígitos', (string) Ajuste::motivoDeDestinoPago('bcp', '0011000200030004005', 'TECTRON GALY EIRL'));
        $this->assertStringContainsString('de prueba', (string) Ajuste::motivoDeDestinoPago('bcp', '00000000000000000000', 'TECTRON GALY EIRL'));
        $this->assertStringContainsString('cuenta de prueba', (string) Ajuste::motivoDeDestinoPago('bcp', '00110002000300040055', 'CUENTA DE PRUEBA - NO TRANSFERIR'));
        $this->assertStringContainsString('empieza con 9', (string) Ajuste::motivoDeDestinoPago('plin', '012345678', 'Galy Tectron', 'pagos/qr/plin.png'));
        $this->assertStringContainsString('código QR', (string) Ajuste::motivoDeDestinoPago('yape', '987654321', 'Galy Tectron', null));
        $this->assertStringContainsString('nombre real', (string) Ajuste::motivoDeDestinoPago('bcp', '00222019808014305223', 'gus gus'));
        $this->assertStringContainsString('nombre real', (string) Ajuste::motivoDeDestinoPago('yape', '987654321', 'BETRILLAN BETRILLAN', 'pagos/qr/yape.png'));
        $this->assertNull(Ajuste::motivoDeDestinoPago('bcp', '00222019808014305223', 'GUSTAVO EDU BEDRILLANA RODRIGUEZ'));
    }

    public function test_a_card_number_is_never_accepted_as_an_account_number(): void
    {
        $this->assertTrue(Ajuste::esNumeroDeTarjeta('4557 8813 2463 3678'));
        $this->assertTrue(Ajuste::esNumeroDeTarjeta('4111111111111111'));
        $this->assertFalse(Ajuste::esNumeroDeTarjeta('191-876543210-0-88'));
        $this->assertFalse(Ajuste::esNumeroDeTarjeta('22098080143052'));
        $this->assertFalse(Ajuste::esNumeroDeTarjeta(null));
        $this->assertFalse(Ajuste::esNumeroDeTarjeta(''));
    }

    public function test_a_placeholder_bcp_account_is_never_offered_as_a_transfer_destination(): void
    {
        $this->configurarAjustes([
            'web_bcp_cci' => '00000000000000000000',
            'web_bcp_titular' => 'CUENTA DE PRUEBA - NO TRANSFERIR',
        ]);

        $this->actingAs($this->clienteListo())
            ->post(route('web.pedidos.pago_manual'), $this->checkout('bcp'))
            ->assertRedirect(route('web.carrito.index'))
            ->assertSessionHas('mensaje', 'Elige otro método de pago por ahora: la tienda aún no puede recibir transferencias por BCP.');
    }

    public function test_a_cci_with_a_typo_is_rejected_before_anyone_transfers_to_it(): void
    {
        $this->configurarAjustes([
            'web_bcp_cci' => '0011000200030004005',
            'web_bcp_titular' => 'TECTRON GALY EIRL',
        ]);

        $this->actingAs($this->clienteListo())
            ->post(route('web.pedidos.pago_manual'), $this->checkout('bcp'))
            ->assertRedirect(route('web.carrito.index'))
            ->assertSessionHas('mensaje', 'Elige otro método de pago por ahora: la tienda aún no puede recibir transferencias por BCP.');
    }

    public function test_a_receiver_number_that_is_not_a_mobile_is_rejected(): void
    {
        $this->configurarAjustes([
            'web_yape_numero' => '222222222',
            'web_yape_titular' => 'TECTRON GALY EIRL',
            'web_yape_qr' => 'pagos/qr/web/yape.png',
        ]);

        $this->actingAs($this->clienteListo())
            ->post(route('web.pedidos.pago_manual'), $this->checkout('yape'))
            ->assertRedirect(route('web.carrito.index'))
            ->assertSessionHas('mensaje', 'Elige otro método de pago por ahora: la tienda aún no puede recibir transferencias por Yape.');
    }

    private function clienteListo(): User
    {
        return User::factory()->create([
            'galy_user_id' => 42,
            'google_id' => 'google-42',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * `direccion_id` no existe a propósito: si el destino de pago pasara el
     * control, el pedido se crearía y el test fallaría al buscar la dirección.
     */
    private function checkout(string $metodo): array
    {
        return [
            'name' => 'Cliente GALY',
            'dni' => '12345678',
            'telefono' => '987654321',
            'privacy_consent' => '1',
            'direccion_id' => 999999,
            'metodo_pago' => $metodo,
            'modalidad_entrega' => 'recojo',
        ];
    }

    private function configurarAjustes(array $datos): void
    {
        DB::table('ajustes')->insert(array_merge([
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
        ], $datos));
    }
}
