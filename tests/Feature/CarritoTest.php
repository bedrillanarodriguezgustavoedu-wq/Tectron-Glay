<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CarritoTest extends TestCase
{
    use RefreshDatabase;

    private function createProductWithSharedInventory(int $stock): Producto
    {
        $product = Producto::factory()->create(['stock' => $stock]);

        DB::connection('galy')->table('productos')->insert([
            'id' => $product->id,
            'categoria_id' => $product->categoria_id,
            'stock' => $stock,
        ]);

        return $product;
    }

    public function test_guest_cannot_add_a_product_to_the_cart(): void
    {
        $producto = $this->createProductWithSharedInventory(5);

        $this->postJson(route('web.carrito.store'), [
            'producto_id' => $producto->id,
            'cantidad' => 1,
        ])->assertUnauthorized();

        $this->assertDatabaseCount('carrito', 0);
    }

    public function test_authenticated_user_can_add_and_increment_a_cart_item(): void
    {
        $usuario = User::factory()->create();
        $usuario->forceFill(['galy_user_id' => $usuario->id])->save();
        $producto = $this->createProductWithSharedInventory(5);

        $this->actingAs($usuario);

        $this->postJson(route('web.carrito.store'), [
            'producto_id' => $producto->id,
            'cantidad' => 2,
        ])->assertOk()
            ->assertJson(['success' => true, 'contador' => 2]);

        $this->postJson(route('web.carrito.store'), [
            'producto_id' => $producto->id,
            'cantidad' => 1,
        ])->assertOk()
            ->assertJson(['success' => true, 'contador' => 3]);

        $this->assertDatabaseHas('carrito', [
            'usuario_id' => $usuario->id,
            'producto_id' => $producto->id,
            'cantidad' => 3,
        ]);
        $this->assertDatabaseCount('carrito', 1);
    }

    public function test_checkout_displays_the_three_purchase_steps_for_an_authenticated_customer(): void
    {
        $usuario = User::factory()->create();
        $usuario->forceFill(['galy_user_id' => $usuario->id])->save();
        $producto = $this->createProductWithSharedInventory(5);
        $producto->forceFill([
            'precio_venta' => 550,
            'precio_oferta' => null,
            'en_oferta' => false,
        ])->save();

        Schema::create('producto_imagenes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->string('imagen')->nullable();
            $table->timestamps();
        });

        DB::table('carrito')->insert([
            'usuario_id' => $usuario->galy_user_id,
            'producto_id' => $producto->id,
            'cantidad' => 1,
        ]);

        $response = $this->actingAs($usuario)->get(route('web.carrito.index'));

        $response->assertOk()
            ->assertSee('Identificación')
            ->assertSee('Entrega')
            ->assertSee('Pago')
            ->assertSee('data-checkout-panel="1"', false)
            ->assertSee('data-checkout-panel="2"', false)
            ->assertSee('data-checkout-panel="3"', false)
            ->assertSee('data-checkout-go="1"', false)
            ->assertSee('data-checkout-go="2"', false)
            ->assertSee('data-checkout-go="3"', false)
            ->assertSee('aria-controls="checkout-panel-3"', false)
            ->assertSee('navigateToCheckoutStep(targetStep)', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="dni"', false)
            ->assertSee('name="telefono"', false)
            ->assertSee('name="privacy_consent"', false)
            ->assertSee(route('web.privacidad'), false)
            ->assertSee('id="checkout-customer-email"', false)
            ->assertSee('La dirección queda asociada a tu pedido, incluso si eliges recojo en tienda.')
            ->assertSee('data-cart-summary-quantity="'.$this->getCartItemId($usuario, $producto).'"', false)
            ->assertSee('Yape, Plin y BCP estarán disponibles')
            ->assertSee('Tu pedido tiene envío estándar gratis.')
            ->assertSee('id="hidden-shipping-option" value="free"', false)
            ->assertSee('id="standard_shipping" value="standard" checked', false)
            ->assertSee('id="express_shipping" value="express"', false)
            ->assertDontSee('id="free_shipping"', false)
            ->assertSee('Método de pago')
            ->assertSee('Compra segura')
            ->assertSee('Tu información está protegida y solo se usa para procesar tu pedido.')
            ->assertSee('class="summary-item d-none" id="subtotal-original-container"', false);

        $this->assertSame(1, substr_count($response->getContent(), 'id="checkout-form"'));
    }

    public function test_the_shipping_amounts_come_from_configuration_and_express_stays_selectable(): void
    {
        config(['tienda.envio' => [
            'umbral_gratis' => 1000,
            'costo_estandar' => 15,
            'costo_express' => 40,
        ]]);

        $usuario = User::factory()->create();
        $usuario->forceFill(['galy_user_id' => $usuario->id])->save();
        $producto = $this->createProductWithSharedInventory(5);
        $producto->forceFill(['precio_venta' => 550, 'precio_oferta' => null, 'en_oferta' => false])->save();

        Schema::create('producto_imagenes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->string('imagen')->nullable();
            $table->timestamps();
        });

        DB::table('carrito')->insert([
            'usuario_id' => $usuario->galy_user_id,
            'producto_id' => $producto->id,
            'cantidad' => 1,
        ]);

        $response = $this->actingAs($usuario)->get(route('web.carrito.index'));

        $response->assertOk()
            ->assertSee('15.00')
            ->assertSee('40.00')
            ->assertSee('Se elimina el cargo en pedidos desde')
            ->assertSee('1,000.00')
            ->assertSee('Te faltan')
            ->assertSee('para obtener envío gratis.')
            ->assertSee('id="hidden-shipping-option" value="standard"', false);

        $html = $response->getContent();
        $this->assertStringNotContainsString('id="express_shipping" value="express" disabled', $html);
        $this->assertStringNotContainsString('id="standard_shipping" value="standard" checked disabled', $html);
        $this->assertStringNotContainsString('1,000.00 disabled', $html);
    }

    public function test_checkout_shows_the_configured_qr_and_payment_details_for_yape_and_plin(): void
    {
        $usuario = User::factory()->create();
        $usuario->forceFill(['galy_user_id' => $usuario->id])->save();
        $producto = $this->createProductWithSharedInventory(5);
        $producto->forceFill([
            'precio_venta' => 550,
            'precio_oferta' => null,
            'en_oferta' => false,
        ])->save();

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
            'yape_numero' => '111111111',
            'yape_titular' => 'Titular exclusivo app',
            'yape_qr' => 'pagos/qr/app/yape.png',
            'web_yape_numero' => '987654321',
            'web_yape_titular' => 'Titular Yape',
            'web_yape_qr' => 'pagos/qr/web/yape.png',
            'plin_numero' => '222222222',
            'plin_titular' => 'Titular Plin app',
            'plin_qr' => 'pagos/qr/app/plin.png',
            'web_plin_numero' => '912345678',
            'web_plin_titular' => 'Titular Plin',
            'web_plin_qr' => 'pagos/qr/web/plin.png',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('carrito')->insert([
            'usuario_id' => $usuario->galy_user_id,
            'producto_id' => $producto->id,
            'cantidad' => 1,
        ]);

        $response = $this->actingAs($usuario)->get(route('web.carrito.index'));

        $response->assertOk()
            ->assertSee('storage/pagos/qr/web/yape.png')
            ->assertSee('storage/pagos/qr/web/plin.png')
            ->assertSee('Código QR oficial de Yape de Titular Yape')
            ->assertSee('Código QR oficial de Plin de Titular Plin')
            ->assertSee('Titular Yape')
            ->assertSee('Titular Plin')
            ->assertDontSee('Titular exclusivo app')
            ->assertDontSee('Titular Plin app')
            ->assertSee('Número: <strong>987654321</strong>', false)
            ->assertSee('Número: <strong>912345678</strong>', false)
            ->assertSee('data-checkout-qr="yape"', false)
            ->assertSee('data-checkout-qr="plin"', false)
            ->assertSee('formatearPrecioJS(total)', false)
            ->assertSee('data-copy-payment-number="987654321"', false)
            ->assertSee('id="payment-copy-feedback"', false)
            ->assertSee('Comprueba que el nombre del destinatario coincida antes de pagar.')
            ->assertSee('Elige un método de pago');
    }

    public function test_checkout_offers_bcp_transfer_when_only_the_web_bcp_account_is_configured(): void
    {
        $usuario = User::factory()->create();
        $usuario->forceFill(['galy_user_id' => $usuario->id])->save();
        $producto = $this->createProductWithSharedInventory(5);
        $producto->forceFill([
            'precio_venta' => 550,
            'precio_oferta' => null,
            'en_oferta' => false,
        ])->save();

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
            'app_bcp_cci' => '00000000000000',
            'app_bcp_titular' => 'Titular BCP exclusivo app',
            'web_bcp_cci' => '00110002000300040055',
            'web_bcp_cuenta' => '191-876543210-0-88',
            'web_bcp_titular' => 'Titular BCP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('carrito')->insert([
            'usuario_id' => $usuario->galy_user_id,
            'producto_id' => $producto->id,
            'cantidad' => 1,
        ]);

        $response = $this->actingAs($usuario)->get(route('web.carrito.index'));

        $response->assertOk()
            ->assertSee('name="metodo_pago" id="pago_bcp" value="bcp"', false)
            ->assertSee('data-checkout-qr="bcp"', false)
            ->assertSee('00110002000300040055')
            ->assertSee('191-876543210-0-88')
            ->assertSee('Titular BCP')
            ->assertDontSee('Titular BCP exclusivo app')
            ->assertSee('data-copy-payment-number="00110002000300040055"', false)
            ->assertSee('Total exacto')
            ->assertDontSee('Yape, Plin y BCP estarán disponibles')
            ->assertSee('<button type="submit" class="btn btn-primary rounded-pill px-4" id="btn-submit-checkout"', false)
            ->assertDontSee('<button type="submit" class="btn btn-primary rounded-pill px-4" id="btn-submit-checkout" disabled', false)
            ->assertSee('id="payment-note-manual"', false)
            ->assertDontSee('id="payment-note-tarjeta"', false)
            ->assertSee('Elige un método de pago');
    }

    public function test_checkout_hides_bcp_while_the_saved_account_is_only_a_placeholder(): void
    {
        $usuario = User::factory()->create();
        $usuario->forceFill(['galy_user_id' => $usuario->id])->save();
        $producto = $this->createProductWithSharedInventory(5);
        $producto->forceFill([
            'precio_venta' => 550,
            'precio_oferta' => null,
            'en_oferta' => false,
        ])->save();

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
            'web_bcp_cci' => '00000000000000000000',
            'web_bcp_cuenta' => '191-00000000-0-00',
            'web_bcp_titular' => 'CUENTA DE PRUEBA - NO TRANSFERIR',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('carrito')->insert([
            'usuario_id' => $usuario->galy_user_id,
            'producto_id' => $producto->id,
            'cantidad' => 1,
        ]);

        $response = $this->actingAs($usuario)->get(route('web.carrito.index'));

        $response->assertOk()
            ->assertDontSee('id="pago_bcp"', false)
            ->assertDontSee('data-checkout-qr="bcp"', false)
            ->assertDontSee('00000000000000000000')
            ->assertDontSee('191-00000000-0-00')
            ->assertDontSee('CUENTA DE PRUEBA - NO TRANSFERIR');
    }

    public function test_checkout_lists_payment_methods_in_yape_bcp_plin_order(): void
    {
        $usuario = User::factory()->create();
        $usuario->forceFill(['galy_user_id' => $usuario->id])->save();
        $producto = $this->createProductWithSharedInventory(5);
        $producto->forceFill([
            'precio_venta' => 550,
            'precio_oferta' => null,
            'en_oferta' => false,
        ])->save();

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

        DB::table('carrito')->insert([
            'usuario_id' => $usuario->galy_user_id,
            'producto_id' => $producto->id,
            'cantidad' => 1,
        ]);

        $html = $this->actingAs($usuario)->get(route('web.carrito.index'))->getContent();

        $posiciones = [
            'Yape' => strpos($html, 'id="pago_yape"'),
            'BCP' => strpos($html, 'id="pago_bcp"'),
            'Plin' => strpos($html, 'id="pago_plin"'),
        ];

        $this->assertGreaterThan(
            $posiciones['Yape'],
            $posiciones['BCP'],
            'BCP debe aparecer despues de Yape en el checkout'
        );
        $this->assertGreaterThan(
            $posiciones['BCP'],
            $posiciones['Plin'],
            'Plin debe aparecer ultimo en el checkout'
        );
    }

    private function getCartItemId(User $usuario, Producto $producto): int
    {
        return (int) DB::table('carrito')
            ->where('usuario_id', $usuario->galy_user_id)
            ->where('producto_id', $producto->id)
            ->value('id');
    }

    public function test_legacy_web_account_is_linked_to_the_matching_galy_user_before_adding_to_cart(): void
    {
        $usuario = User::factory()->create(['email' => 'shared@example.com']);
        $producto = $this->createProductWithSharedInventory(5);
        $galyUserId = DB::connection('galy')->table('usuarios')->insertGetId([
            'nombre' => $usuario->name,
            'email' => 'SHARED@example.com',
            'password' => 'unused',
        ]);

        $this->actingAs($usuario)
            ->postJson(route('web.carrito.store'), [
                'producto_id' => $producto->id,
                'cantidad' => 1,
            ])->assertOk()
            ->assertJson(['success' => true, 'contador' => 1]);

        $this->assertDatabaseHas('users', [
            'id' => $usuario->id,
            'galy_user_id' => $galyUserId,
        ]);
        $this->assertDatabaseHas('carrito', [
            'usuario_id' => $galyUserId,
            'producto_id' => $producto->id,
            'cantidad' => 1,
        ]);
    }

    public function test_local_account_without_a_matching_galy_user_gets_a_clear_cart_error(): void
    {
        $usuario = User::factory()->create();
        $producto = $this->createProductWithSharedInventory(5);

        $this->actingAs($usuario)
            ->postJson(route('web.carrito.store'), [
                'producto_id' => $producto->id,
                'cantidad' => 1,
            ])->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'No se encontró una cuenta GALY vinculada a este correo. Inicia sesión con tu cuenta GALY para compartir el carrito.',
            ]);

        $this->assertDatabaseCount('carrito', 0);
    }

    public function test_cart_rejects_quantity_above_available_stock(): void
    {
        $usuario = User::factory()->create();
        $usuario->forceFill(['galy_user_id' => $usuario->id])->save();
        $producto = $this->createProductWithSharedInventory(2);

        $this->actingAs($usuario)
            ->postJson(route('web.carrito.store'), [
                'producto_id' => $producto->id,
                'cantidad' => 3,
            ])->assertBadRequest()
            ->assertJson(['success' => false]);

        $this->assertDatabaseCount('carrito', 0);
    }

    public function test_cart_rejects_an_increment_that_would_exceed_available_stock(): void
    {
        $usuario = User::factory()->create();
        $usuario->forceFill(['galy_user_id' => $usuario->id])->save();
        $producto = $this->createProductWithSharedInventory(2);

        $this->actingAs($usuario)
            ->postJson(route('web.carrito.store'), [
                'producto_id' => $producto->id,
                'cantidad' => 2,
            ])->assertOk();

        $this->postJson(route('web.carrito.store'), [
            'producto_id' => $producto->id,
            'cantidad' => 1,
        ])->assertBadRequest()
            ->assertJson(['success' => false]);

        $this->assertDatabaseHas('carrito', [
            'usuario_id' => $usuario->id,
            'producto_id' => $producto->id,
            'cantidad' => 2,
        ]);
        $this->assertDatabaseCount('carrito', 1);
    }

    public function test_cart_rejects_invalid_quantity(): void
    {
        $usuario = User::factory()->create();
        $usuario->forceFill(['galy_user_id' => $usuario->id])->save();
        $producto = $this->createProductWithSharedInventory(5);

        $this->actingAs($usuario)
            ->postJson(route('web.carrito.store'), [
                'producto_id' => $producto->id,
                'cantidad' => 0,
            ])->assertUnprocessable();

        $this->assertDatabaseCount('carrito', 0);
    }
}
