<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AjustesPagoTarjetaTest extends TestCase
{
    use RefreshDatabase;

    private function administrarAjustes(): User
    {
        $permiso = Permission::firstOrCreate([
            'name' => 'Ajustes del sistema',
            'guard_name' => 'web',
        ]);
        $rol = Role::firstOrCreate(['name' => 'ADMINISTRADOR', 'guard_name' => 'web']);
        $rol->givePermissionTo($permiso);

        $admin = User::factory()->create();
        $admin->assignRole($rol);

        Schema::create('producto_imagenes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->string('imagen')->nullable();
            $table->timestamps();
        });

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

        return $admin;
    }

    public function test_the_settings_page_offers_the_card_switch_and_warns_about_missing_credentials(): void
    {
        config(['services.mercadopago.enabled' => false]);

        $this->actingAs($this->administrarAjustes())
            ->get(route('admin.ajustes.index'))
            ->assertOk()
            ->assertSee('name="web_mercadopago_activa"', false)
            ->assertSee('Faltan credenciales')
            ->assertSee('Escribe MERCADOPAGO_ENABLED=true y MERCADOPAGO_ACCESS_TOKEN=… en el archivo')
            ->assertDontSee('Pasarela configurada');
    }

    /**
     * El dueño ya dejó MERCADOPAGO_ENABLED=true en .env, así que repetirle esa
     * parte hacía que el aviso señalara algo que ya estaba bien. El aviso solo
     * puede nombrar lo que falte de verdad.
     */
    public function test_the_warning_names_only_the_credential_that_is_actually_missing(): void
    {
        config([
            'services.mercadopago.enabled' => true,
            'services.mercadopago.access_token' => null,
        ]);

        $this->actingAs($this->administrarAjustes())
            ->get(route('admin.ajustes.index'))
            ->assertOk()
            ->assertSee('Escribe MERCADOPAGO_ACCESS_TOKEN=… en el archivo')
            ->assertDontSee('MERCADOPAGO_ENABLED=true y');
    }

    public function test_the_settings_page_confirms_the_gateway_once_it_is_configured(): void
    {
        $admin = $this->administrarAjustes();
        config([
            'services.mercadopago.enabled' => true,
            'services.mercadopago.access_token' => 'TEST-8514936306504285-091415-3733c7f9ab12',
        ]);
        app(\App\Services\MercadoPagoService::class)->registrarVerificacion();

        $this->actingAs($admin)
            ->get(route('admin.ajustes.index'))
            ->assertOk()
            ->assertSee('Pasarela configurada')
            ->assertDontSee('Faltan credenciales');
    }

    /**
     * Un .env con un token escrito no significa que la tarjeta pueda ofrecerse:
     * sin la prueba contra Mercado Pago el checkout la mantiene retirada, y el
     * panel no puede decir lo contrario.
     */
    public function test_the_settings_page_asks_for_the_credential_test_before_claiming_the_gateway_is_ready(): void
    {
        config([
            'services.mercadopago.enabled' => true,
            'services.mercadopago.access_token' => 'TEST-8514936306504285-091415-3733c7f9ab12',
        ]);

        $this->assertFalse(app(\App\Services\MercadoPagoService::class)->sePuedeOfrecer());

        $this->actingAs($this->administrarAjustes())
            ->get(route('admin.ajustes.index'))
            ->assertOk()
            ->assertSee('Credencial sin comprobar')
            // El panel no puede decir que la tarjeta "no se ofrece" cuando el
            // carrito la muestra gris con «Próximamente»: el dueño corre el
            // diagnóstico buscando algo que ya ve.
            ->assertSee('ya se ve gris con el aviso')
            ->assertDontSee('Pasarela configurada');
    }

    /**
     * Escribir un valor de ejemplo en el .env es peor que dejarlo vacío: el
     * checkout ofrecería la tarjeta y el cliente caería dentro de Mercado Pago.
     */
    public function test_a_placeholder_token_does_not_claim_the_gateway_is_ready(): void
    {
        config([
            'services.mercadopago.enabled' => true,
            'services.mercadopago.access_token' => 'APP_USR-xxxxxxxx',
        ]);

        $this->assertFalse(app(\App\Services\MercadoPagoService::class)->habilitado());

        $this->actingAs($this->administrarAjustes())
            ->get(route('admin.ajustes.index'))
            ->assertOk()
            ->assertSee('Escribe MERCADOPAGO_ACCESS_TOKEN=… en el archivo');
    }

    /**
     * Rojo y «rechazó» se leían como dinero perdido: el dueño respondía
     * «arreglar» y volvía a intentar el pago. El estado real es un medio de pago
     * en pausa, y eso es lo que hay que escribir.
     */
    public function test_a_rejected_credential_is_reported_as_a_pause_not_as_an_alarm(): void
    {
        config([
            'services.mercadopago.enabled' => true,
            'services.mercadopago.access_token' => 'TEST-8514936306504285-091415-3733c7f9ab12',
        ]);

        $usuario = $this->administrarAjustes();
        // Solo se puede poner en pausa una tarjeta que estaba ofrecida.
        app(\App\Services\MercadoPagoService::class)->registrarVerificacion();
        app(\App\Services\MercadoPagoService::class)->bloquearPorRechazo();

        $this->actingAs($usuario)
            ->get(route('admin.ajustes.index'))
            ->assertOk()
            ->assertSee('Tarjeta en pausa')
            ->assertSee('Nadie puede pagar mal por este motivo')
            ->assertDontSee('Mercado Pago rechazó el token')
            ->assertDontSee('bg-danger">Mercado Pago');
    }
}
