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
            // Sin errores el cliente debe empezar por sus datos: si arranca en un
            // paso posterior, el indicador le pinta un check verde en
            // "Identificación" aunque el DNI y el teléfono sigan vacíos.
            ->assertSee('data-initial-step="1"', false)
            ->assertSee('data-checkout-go="1"', false)
            ->assertSee('data-checkout-go="2"', false)
            ->assertSee('data-checkout-go="3"', false)
            ->assertSee('aria-controls="checkout-panel-3"', false)
            ->assertSee('Entrega de Productos')
            ->assertSee('Forma de Pago')
            ->assertSee('id="modalidad_envio" value="envio" checked', false)
            ->assertSee('id="modalidad_recojo" value="recojo"', false)
            ->assertSee('id="modalidad_provincia" value="provincia"', false)
            ->assertSee('Envío a provincias')
            ->assertSee('El costo del envío a provincias se coordina contigo por WhatsApp o teléfono antes de despachar', false)
            ->assertSee('navigateToCheckoutStep(targetStep)', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="dni"', false)
            ->assertSee('name="telefono"', false)
            ->assertSee('name="privacy_consent"', false)
            ->assertSee(route('web.privacidad'), false)
            ->assertSee('id="checkout-customer-email"', false)
            ->assertSee('Usamos esta dirección para despachar tu pedido.')
            // Cada modalidad abre su propio conjunto de campos: la nota de
            // provincias y el punto de recojo existen, ambos ocultos por defecto.
            ->assertSee('id="provincia-note"', false)
            ->assertSee('id="punto-recojo-card"', false)
            ->assertSee('Punto de recojo')
            ->assertSee('const muestraDireccion = deliveryMode !== \'recojo\';', false)
            ->assertSee('if (addressSelect) addressSelect.required = muestraDireccion && hayDirecciones;', false)
            ->assertSee('!esRecojoEnTienda() && (!addressSelect || !addressSelect.value)', false)
            ->assertSee('data-cart-summary-quantity="'.$this->getCartItemId($usuario, $producto).'"', false)
            ->assertSee('Yape y Plin estarán disponibles cuando la tienda configure los datos de cada método.')
            // La transferencia BCP salió del carrito: aunque la cuenta esté en
            // Ajustes, su tarjeta no debe llegar al HTML.
            ->assertDontSee('id="pago_bcp"', false)
            ->assertSee('Tu pedido tiene envío estándar gratis.')
            ->assertSee('id="hidden-shipping-option" value="free"', false)
            ->assertSee('id="standard_shipping" value="standard" checked', false)
            ->assertSee('id="express_shipping" value="express"', false)
            ->assertDontSee('id="free_shipping"', false)
            ->assertSee('Forma de Pago')
            ->assertSee('Compra segura')
            ->assertSee('Tu información está protegida y solo se usa para procesar tu pedido.')
            ->assertSee('class="summary-item d-none" id="subtotal-original-container"', false);

        $this->assertSame(1, substr_count($response->getContent(), 'id="checkout-form"'));

        // Cada paso es su propio <section>: si un contenedor queda sin cerrar, el
        // navegador lo reacomoda solo y el desalineado lo ve el cliente, no una prueba.
        $html = $response->getContent();
        $titulos = [
            1 => 'Tus datos personales',
            2 => 'Entrega de Productos',
            3 => 'Forma de Pago',
        ];

        foreach ($titulos as $paso => $titulo) {
            $inicio = mb_strpos($html, 'id="checkout-panel-'.$paso.'"');
            $this->assertNotFalse($inicio, 'El carrito no renderiza el paso '.$paso.'.');

            $fin = mb_strpos($html, '</section>', $inicio);
            $this->assertNotFalse($fin, 'El paso '.$paso.' no cierra antes del siguiente.');

            $panel = mb_substr($html, $inicio, $fin - $inicio);
            $this->assertStringContainsString($titulo, $panel);

            foreach ($titulos as $otroPaso => $otroTitulo) {
                if ($otroPaso !== $paso) {
                    $this->assertStringNotContainsString($otroTitulo, $panel);
                }
            }

            $this->assertSame(
                substr_count($panel, '<div'),
                substr_count($panel, '</div>'),
                'El paso '.$paso.' deja contenedores <div> sin cerrar.'
            );
        }
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
            // El dueño quitó el celular de la pantalla de pago: el cliente usa el QR.
            ->assertDontSee('987654321')
            ->assertDontSee('912345678')
            ->assertSee('data-checkout-qr="yape"', false)
            ->assertSee('data-checkout-qr="plin"', false)
            ->assertSee('formatearPrecioJS(total)', false)
            ->assertDontSee('data-copy-payment-number="987654321"', false)
            ->assertSee('id="payment-copy-feedback"', false)
            ->assertSee('Comprueba que el nombre del destinatario coincida antes de pagar.')
            ->assertSee('Elige un método de pago');
    }

    public function test_el_carrito_ya_no_ofrece_bcp_aunque_la_cuenta_este_configurada(): void
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

        // Retirada por decisión del dueño (2026-10-21): ni el botón ni el CCI,
        // aunque Ajustes tenga la cuenta completa y válida.
        $response->assertOk()
            ->assertDontSee('id="pago_bcp"', false)
            ->assertDontSee('data-checkout-qr="bcp"', false)
            ->assertDontSee('00110002000300040055')
            ->assertDontSee('191-876543210-0-88')
            ->assertDontSee('Titular BCP')
            ->assertDontSee('Transferencia BCP')
            // Sin Yape ni Plin configurados no queda ningún método manual.
            ->assertSee('Yape y Plin estarán disponibles cuando la tienda configure los datos de cada método.');
    }

    public function test_checkout_lists_payment_methods_in_yape_plin_order(): void
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

        // El CCI está cargado en Ajustes a propósito: retirar BCP del carrito es
        // una decisión de vitrina, no de configuración, así que el HTML no debe
        // mostrarlo ni desordenar a los métodos que sí quedan.
        $this->assertFalse(strpos($html, 'id="pago_bcp"'), 'El checkout ya no debe ofrecer BCP.');
        $this->assertFalse(strpos($html, '00110002000300040055'), 'El CCI guardado no debe publicarse.');

        $posiciones = [
            'Yape' => strpos($html, 'id="pago_yape"'),
            'Plin' => strpos($html, 'id="pago_plin"'),
        ];

        // La referencia que eligió el dueño ordena la lista tarjeta → código QR →
        // Yape, así que Plin quedó delante de Yape. Sigue sin aparecer BCP.
        $this->assertLessThan(
            $posiciones['Yape'],
            $posiciones['Plin'],
            'El código QR debe aparecer antes que Yape en el checkout'
        );

        // Los QR suben como foto de pantalla: sin agrandarlos el cliente no puede
        // escanearlos, así que el zoom forma parte del pago y no del adorno.
        foreach (['Yape', 'Plin'] as $metodo) {
            $this->assertMatchesRegularExpression(
                '/<button[^>]+data-qr-zoom="'.$metodo.'"/',
                $html,
                'El QR de '.$metodo.' debe ser un botón que abra el modal.'
            );
        }

        $this->assertTrue(
            str_contains($html, 'id="qrZoomModal"'),
            'Sin el modal de agrado, el QR queda ilegible en el celular.'
        );
    }

    /**
     * Un `<input disabled>` no es enviable: si el script desactiva las cantidades
     * antes de armar el FormData, el navegador manda el formulario vacío y el
     * servidor contesta «El campo carrito id es obligatorio».
     */
    public function test_el_script_de_guardar_cantidades_recoje_el_formdata_antes_de_desactivar_los_inputs(): void
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
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('carrito')->insert([
            'usuario_id' => $usuario->galy_user_id,
            'producto_id' => $producto->id,
            'cantidad' => 1,
        ]);

        $html = $this->actingAs($usuario)->get(route('web.carrito.index'))->getContent();

        $inicio = strpos($html, "const formActualizar = document.getElementById('form-actualizar-carrito');");
        $this->assertNotFalse($inicio, 'La página debe seguir montando el guardado AJAX del carrito.');

        $bloque = substr($html, $inicio, 2500);

        $recoleccion = strpos($bloque, 'new FormData(this)');
        $desactivacion = strpos($bloque, 'control.disabled = true');

        $this->assertNotFalse($recoleccion, 'El guardado del carrito debe armar un FormData.');
        $this->assertNotFalse($desactivacion, 'El guardado del carrito debe bloquear los controles mientras escribe.');
        $this->assertLessThan($desactivacion, $recoleccion,
            'El FormData debe armarse antes de desactivar las cantidades: desactivadas, no viajan y el carrito llega vacío al servidor.');
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

    public function test_envio_a_provincias_is_accepted_but_an_unknown_modality_is_not(): void
    {
        $usuario = User::factory()->create();
        $usuario->forceFill(['galy_user_id' => $usuario->id])->save();

        $datos = [
            'name' => $usuario->name,
            'dni' => '12345678',
            'telefono' => '987654321',
            'privacy_consent' => '1',
            'direccion_id' => 1,
            'metodo_pago' => 'yape',
        ];

        // La validación corre antes que cualquier otro guard, así que un error de
        // modalidad aparecería aunque el carrito esté vacío.
        $this->actingAs($usuario)
            ->post(route('web.pedidos.pago_manual'), $datos + [
                'modalidad_entrega' => 'provincia',
                'shipping_option' => 'provincia',
            ])
            ->assertSessionDoesntHaveErrors(['modalidad_entrega', 'shipping_option']);

        $this->actingAs($usuario)
            ->post(route('web.pedidos.pago_manual'), $datos + [
                'modalidad_entrega' => 'helicoptero',
            ])
            ->assertSessionHasErrors('modalidad_entrega');
    }

    /**
     * Elegir recojo y recibir un error de validación no puede devolver al cliente
     * al formulario con la modalidad reiniciada a «envío» y la dirección otra vez
     * en pantalla.
     */
    public function test_la_modalidad_elegida_vuelve_marcada_tras_un_error_de_validacion(): void
    {
        $usuario = User::factory()->create();
        $usuario->forceFill(['galy_user_id' => $usuario->id])->save();
        $producto = $this->createProductWithSharedInventory(5);
        $producto->forceFill([
            'precio_venta' => 550,
            'precio_oferta' => null,
            'en_oferta' => false,
        ])->save();

        DB::table('carrito')->insert([
            'usuario_id' => $usuario->galy_user_id,
            'producto_id' => $producto->id,
            'cantidad' => 1,
        ]);

        Schema::create('producto_imagenes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->string('imagen')->nullable();
            $table->timestamps();
        });

        $this->actingAs($usuario)
            ->from(route('web.carrito.index'))
            ->post(route('web.pedidos.pago_manual'), [
                'name' => $usuario->name,
                'dni' => '1234',
                'telefono' => '987654321',
                'privacy_consent' => '1',
                'metodo_pago' => 'yape',
                'modalidad_entrega' => 'recojo',
                'shipping_option' => 'pickup',
            ])
            ->assertSessionHasErrors('dni');

        $respuesta = $this->actingAs($usuario)->get(route('web.carrito.index'))->assertOk();

        foreach (['id="modalidad_recojo" value="recojo" checked', 'id="shipping-address-container" style="display: none"', 'id="shipping-options-container" style="display: none"', 'id="punto-recojo-card"'] as $needle) {
            $this->assertTrue(str_contains($respuesta->getContent(), $needle), 'Falta en el HTML: '.$needle);
        }
    }

    public function test_cada_modalidad_de_entrega_tiene_su_propio_texto_en_el_pedido(): void
    {
        $this->assertSame('Envío a domicilio', (new \App\Models\Orden(['modalidad_entrega' => 'envio']))->getModalidadTexto());
        $this->assertSame('Recojo en tienda', (new \App\Models\Orden(['modalidad_entrega' => 'recojo']))->getModalidadTexto());
        $this->assertSame('Envío a provincias', (new \App\Models\Orden(['modalidad_entrega' => 'provincia']))->getModalidadTexto());
        $this->assertSame('Envío a domicilio', (new \App\Models\Orden(['modalidad_entrega' => null]))->getModalidadTexto());
    }
}
