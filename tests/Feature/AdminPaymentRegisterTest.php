<?php

namespace Tests\Feature;

use App\Models\IntentoPagoMercadoPago;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Feature\Concerns\CreatesOrderPaymentTables;
use Tests\TestCase;

class AdminPaymentRegisterTest extends TestCase
{
    use CreatesOrderPaymentTables;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearTablaIntentosMercadoPago();

        Schema::create('pedidos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->decimal('total', 10, 2);
            $table->string('divisa')->default('PEN');
            $table->string('estado_pago');
            $table->string('estado')->default('PENDIENTE');
            $table->string('transaccion_id')->nullable();
            $table->string('metodo_entrega')->default('DOMICILIO');
            $table->text('direccion')->nullable();
            $table->string('cliente_nombre')->nullable();
            $table->string('cliente_email')->nullable();
            $table->string('metodo_pago')->default('yape');
            $table->timestamp('fecha')->nullable();
            $table->timestamps();
        });

        Schema::create('pedido_productos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pedido_id');
            $table->unsignedBigInteger('producto_id');
            $table->integer('cantidad');
            $table->decimal('precio', 10, 2);
            $table->timestamps();
        });

        Schema::create('pedido_pagos_manuales', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pedido_id')->unique();
            $table->unsignedBigInteger('usuario_id');
            $table->string('metodo_pago');
            $table->string('comprobante_path')->nullable();
            $table->string('numero_operacion', 60)->nullable();
            $table->string('estado')->default('pendiente');
            $table->timestamp('comprobante_enviado_at')->nullable();
            $table->timestamp('confirmado_at')->nullable();
            $table->unsignedBigInteger('confirmado_por')->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('pedido_historials');
        Schema::create('pedido_historials', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('orden_id');
            $table->string('estado_anterior')->nullable();
            $table->string('estado_nuevo');
            $table->date('fecha');
            $table->time('hora');
            $table->text('nota');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->timestamps();
        });
    }

    public function test_admin_can_open_the_payment_register_and_see_payment_review_state(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'Listado de Pedidos',
            'guard_name' => 'web',
        ]);
        $processPermission = Permission::firstOrCreate([
            'name' => 'Procesar Pedido',
            'guard_name' => 'web',
        ]);
        $role = Role::firstOrCreate([
            'name' => 'ADMINISTRADOR',
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo($permission);
        $role->givePermissionTo($processPermission);

        $admin = User::factory()->create();
        $admin->assignRole($role);
        $this->actingAs($admin);

        $usuarioGalyId = \Illuminate\Support\Facades\DB::connection('galy')->table('usuarios')->insertGetId([
            'nombre' => 'Cliente de prueba',
            'email' => 'cliente@example.test',
            'password' => 'hashed-password',
        ]);
        $pedidoId = \Illuminate\Support\Facades\DB::table('pedidos')->insertGetId([
            'usuario_id' => $usuarioGalyId,
            'total' => 129.90,
            'divisa' => 'PEN',
            'estado_pago' => 'Pendiente',
            'estado' => 'PENDIENTE',
            'transaccion_id' => 'TEST-PAY-001',
            'metodo_entrega' => 'DOMICILIO',
            'direccion' => 'Dirección de prueba',
            'metodo_pago' => 'yape',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('pedido_pagos_manuales')->insert([
            'pedido_id' => $pedidoId,
            'usuario_id' => $usuarioGalyId,
            'metodo_pago' => 'yape',
            'comprobante_path' => 'comprobantes-pago/test.webp',
            'estado' => 'comprobante_enviado',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('admin.pagos.index', ['revision' => 'por_revisar']))
            ->assertOk()
            ->assertSee('Registro de pagos')
            ->assertSee('TEST-PAY-001')
            ->assertSee('Yape')
            ->assertSee('Comprobantes por revisar')
            ->assertSee('Ver comprobante')
            ->assertSee('Verifica el abono en la app antes de confirmar.')
            ->assertSee('129.90')
            ->assertSee(route('admin.pedidos.comprobante', $pedidoId), false);
    }

    public function test_payment_register_separates_review_queue_from_waiting_and_legacy_records(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'Listado de Pedidos',
            'guard_name' => 'web',
        ]);
        $role = Role::firstOrCreate([
            'name' => 'ADMINISTRADOR',
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo($permission);
        $admin = User::factory()->create();
        $admin->assignRole($role);
        $this->actingAs($admin);

        $waitingId = $this->createPendingManualPayment('pendiente', false);
        $legacyId = $this->createLegacyPayment();

        $this->get(route('admin.pagos.index'))
            ->assertOk()
            ->assertSee('Comprobantes por revisar')
            ->assertSee('Esperando verificación')
            ->assertSee('#'.$waitingId)
            ->assertDontSee('#'.$legacyId);

        $this->get(route('admin.pagos.index', ['revision' => 'esperando']))
            ->assertOk()
            ->assertSee('#'.$waitingId)
            ->assertSee('Cliente de prueba')
            ->assertSee('cliente-pago@example.test')
            ->assertSee('Pedido registrado; verifica el abono directamente en la app bancaria.')
            ->assertDontSee('#'.$legacyId);

        $this->get(route('admin.pagos.index', ['revision' => 'anteriores']))
            ->assertOk()
            ->assertSee('#'.$legacyId)
            ->assertSee('Registro anterior')
            ->assertDontSee('#'.$waitingId);

        $this->get(route('admin.pagos.index', ['revision' => 'todos']))
            ->assertOk()
            ->assertSee('#'.$legacyId)
            ->assertSee('#'.$waitingId);
    }

    public function test_orders_created_by_the_app_show_and_sort_by_their_legacy_fecha(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'Listado de Pedidos',
            'guard_name' => 'web',
        ]);
        $role = Role::firstOrCreate([
            'name' => 'ADMINISTRADOR',
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo($permission);
        $admin = User::factory()->create();
        $admin->assignRole($role);
        $this->actingAs($admin);

        // La API con la que crea pedidos la app escribe `fecha` y deja
        // `created_at` en NULL: antes de esto el panel los mostraba sin fecha.
        $desdeApp = \Illuminate\Support\Facades\DB::table('pedidos')->insertGetId([
            'total' => 1842.85,
            'divisa' => 'USD',
            'estado_pago' => 'Pendiente',
            'metodo_pago' => 'yape',
            'cliente_nombre' => 'Usuario Galy',
            'cliente_email' => 'usuario.galy@gmail.com',
            'fecha' => '2026-10-18 11:45:44',
        ]);
        $desdeWeb = \Illuminate\Support\Facades\DB::table('pedidos')->insertGetId([
            'total' => 80.00,
            'divisa' => 'PEN',
            'estado_pago' => 'Pendiente',
            'metodo_pago' => 'bcp',
            'cliente_nombre' => 'Cliente web',
            'cliente_email' => 'cliente-web@example.test',
            'created_at' => '2026-10-17 09:00:00',
            'updated_at' => '2026-10-17 09:00:00',
        ]);

        $html = $this->get(route('admin.pagos.index', ['revision' => 'todos']))
            ->assertOk()
            ->assertSee('18/10/2026')
            ->assertSee('11:45')
            ->assertDontSee('Fecha no disponible')
            ->getContent();

        $this->assertLessThan(
            strpos($html, '>#'.$desdeWeb.'<'),
            strpos($html, '>#'.$desdeApp.'<')
        );
    }

    public function test_card_payments_show_their_mercadopago_attempt_and_stay_out_of_the_manual_queue(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'Listado de Pedidos',
            'guard_name' => 'web',
        ]);
        $role = Role::firstOrCreate([
            'name' => 'ADMINISTRADOR',
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo($permission);
        $admin = User::factory()->create();
        $admin->assignRole($role);
        $this->actingAs($admin);

        $manualId = $this->createPendingManualPayment('pendiente', false);
        $cardId = $this->createCardPayment();

        $this->get(route('admin.pagos.index', ['revision' => 'esperando']))
            ->assertOk()
            ->assertSee('#'.$manualId)
            ->assertSee('Pagos con tarjeta')
            ->assertDontSee('#'.$cardId);

        $this->get(route('admin.pagos.index', ['revision' => 'tarjetas']))
            ->assertOk()
            ->assertSee('#'.$cardId)
            ->assertSee('Esperando el pago')
            ->assertSee('MP-PAY-'.$cardId)
            ->assertDontSee('#'.$manualId);

        $this->get(route('admin.pagos.index', ['metodo' => 'tarjeta']))
            ->assertOk()
            ->assertSee('#'.$cardId)
            ->assertDontSee('#'.$manualId);

        $this->get(route('admin.pagos.index', ['revision' => 'todos']))
            ->assertOk()
            ->assertSee('#'.$cardId)
            ->assertSee('#'.$manualId);
    }

    public function test_card_rows_explain_what_mercado_pago_reported_or_that_no_attempt_was_opened(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'Listado de Pedidos',
            'guard_name' => 'web',
        ]);
        $role = Role::firstOrCreate([
            'name' => 'ADMINISTRADOR',
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo($permission);
        $admin = User::factory()->create();
        $admin->assignRole($role);
        $this->actingAs($admin);

        $rechazadoId = $this->createCardPayment(
            attemptState: IntentoPagoMercadoPago::ESTADO_FALLIDO,
            gatewayState: 'rejected',
            gatewayDetail: 'cc_rejected_insufficient_amount',
        );
        $sinIntentoId = $this->createCardPayment(withAttempt: false);

        $this->get(route('admin.pagos.index', ['revision' => 'tarjetas']))
            ->assertOk()
            ->assertSee('#'.$rechazadoId)
            ->assertSee('Pago rechazado')
            ->assertSee('Mercado Pago: rejected · cc_rejected_insufficient_amount')
            ->assertSee('#'.$sinIntentoId)
            ->assertSee('Sin intento abierto')
            ->assertSee('El cliente no llegó a abrir la pasarela');
    }

    public function test_an_approved_payment_that_does_not_match_the_order_is_flagged_for_review(): void
    {
        $admin = $this->paymentAdmin();
        Role::findByName('ADMINISTRADOR')
            ->givePermissionTo(Permission::firstOrCreate(['name' => 'Listado de Pedidos', 'guard_name' => 'web']));

        $pedidoId = $this->createCardPayment(
            attemptState: IntentoPagoMercadoPago::ESTADO_ESPERANDO,
            gatewayState: 'approved',
            gatewayDetail: 'Pagado pero sin verificar: el monto cobrado (5.5) no coincide con el del pedido (550)',
        );

        $this->actingAs($admin)
            ->get(route('admin.pagos.index', ['revision' => 'tarjetas']))
            ->assertOk()
            ->assertSee('#'.$pedidoId)
            ->assertSee('Cobro por verificar')
            ->assertSee('Mercado Pago reportó un cobro aprobado que no cuadra con el pedido', false);
    }

    /**
     * El dinero volvió al cliente después de pagar: el pedido no se revierte solo,
     * pero la tienda tiene que verlo antes de enviar la mercancía.
     */
    public function test_a_paid_order_with_a_reversed_card_charge_is_flagged(): void
    {
        $admin = $this->paymentAdmin();
        Role::findByName('ADMINISTRADOR')
            ->givePermissionTo(Permission::firstOrCreate(['name' => 'Listado de Pedidos', 'guard_name' => 'web']));

        $pedidoId = $this->createCardPayment(
            attemptState: IntentoPagoMercadoPago::ESTADO_FALLIDO,
            gatewayState: 'charged_back',
            gatewayDetail: 'reversed by customer claim',
        );
        \Illuminate\Support\Facades\DB::table('pedidos')
            ->where('id', $pedidoId)
            ->update(['estado_pago' => 'Pagado']);

        $this->actingAs($admin)
            ->get(route('admin.pagos.index', ['revision' => 'todos']))
            ->assertOk()
            ->assertSee('#'.$pedidoId)
            ->assertSee('Cobro revertido')
            ->assertSee('Mercado Pago devolvió el cobro (charged_back) de este pedido marcado como pagado', false);
    }

    public function test_card_orders_need_explicit_mercadopago_confirmation_before_being_marked_paid(): void
    {
        $admin = $this->paymentAdmin();
        $pedidoId = $this->createCardPayment();

        $this->actingAs($admin)
            ->from(route('admin.pedidos.create', $pedidoId))
            ->post(route('admin.pedidos.marcar_pagado', $pedidoId))
            ->assertRedirect(route('admin.pedidos.create', $pedidoId))
            ->assertSessionHas('mensaje', 'Mercado Pago confirma los pagos con tarjeta solo. Marca la casilla únicamente si ya ves el cobro aprobado en tu cuenta.');

        $this->assertDatabaseHas('pedidos', [
            'id' => $pedidoId,
            'estado_pago' => 'Pendiente',
        ]);
        $this->assertDatabaseCount('pedido_historials', 0);

        $this->actingAs($admin)
            ->from(route('admin.pedidos.create', $pedidoId))
            ->post(route('admin.pedidos.marcar_pagado', $pedidoId), [
                'verificado_en_mercadopago' => '1',
            ])
            ->assertSessionHas('mensaje', 'El estado del pago se ha actualizado a: Pagado');

        $this->assertDatabaseHas('pedidos', [
            'id' => $pedidoId,
            'estado_pago' => 'Pagado',
        ]);
        $this->assertDatabaseHas('pedido_historials', [
            'orden_id' => $pedidoId,
            'nota' => 'El administrador confirmó el cobro con tarjeta revisándolo en su cuenta de Mercado Pago.',
        ]);
    }

    public function test_manual_payment_cannot_be_confirmed_without_explicit_app_verification(): void
    {
        $admin = $this->paymentAdmin();
        $pedidoId = $this->createPendingManualPayment();

        $this->actingAs($admin)
            ->from('/admin/pagos')
            ->post(route('admin.pedidos.marcar_pagado', $pedidoId))
            ->assertRedirect('/admin/pagos')
            ->assertSessionHas('mensaje', 'Antes de confirmar, verifica en la app de Yape o Plin que el dinero haya llegado.');

        $this->assertDatabaseHas('pedidos', [
            'id' => $pedidoId,
            'estado_pago' => 'Pendiente',
        ]);
        $this->assertDatabaseHas('pedido_pagos_manuales', [
            'pedido_id' => $pedidoId,
            'estado' => 'comprobante_enviado',
        ]);
        $this->assertDatabaseCount('pedido_historials', 0);
    }

    public function test_admin_can_confirm_a_manual_payment_after_verifying_it_in_the_app(): void
    {
        $admin = $this->paymentAdmin();
        $pedidoId = $this->createPendingManualPayment();

        $this->actingAs($admin)
            ->from('/admin/pagos')
            ->post(route('admin.pedidos.marcar_pagado', $pedidoId), [
                'verificado_en_app' => '1',
            ])
            ->assertRedirect('/admin/pagos')
            ->assertSessionHas('mensaje', 'El estado del pago se ha actualizado a: Pagado');

        $this->assertDatabaseHas('pedidos', [
            'id' => $pedidoId,
            'estado_pago' => 'Pagado',
        ]);
        $this->assertDatabaseHas('pedido_pagos_manuales', [
            'pedido_id' => $pedidoId,
            'estado' => 'confirmado',
            'confirmado_por' => $admin->id,
        ]);
        $this->assertDatabaseHas('pedido_historials', [
            'orden_id' => $pedidoId,
            'nota' => 'El administrador verificó en la app de pago el abono y confirmó el comprobante de Yape.',
        ]);
    }

    public function test_admin_can_confirm_yape_order_without_uploaded_receipt_after_bank_verification(): void
    {
        $admin = $this->paymentAdmin();
        $usuarioGalyId = \Illuminate\Support\Facades\DB::connection('galy')->table('usuarios')->insertGetId([
            'nombre' => 'Cliente GALY',
            'email' => 'cliente-sin-comprobante@example.test',
            'password' => 'hashed-password',
        ]);
        $pedidoId = \Illuminate\Support\Facades\DB::table('pedidos')->insertGetId([
            'usuario_id' => $usuarioGalyId,
            'total' => 129.90,
            'divisa' => 'PEN',
            'estado_pago' => 'Pendiente',
            'estado' => 'PENDIENTE',
            'metodo_pago' => 'yape',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.pedidos.create', $pedidoId))
            ->post(route('admin.pedidos.marcar_pagado', $pedidoId), [
                'verificado_en_app' => '1',
            ])
            ->assertRedirect(route('admin.pedidos.create', $pedidoId))
            ->assertSessionHas('mensaje', 'El estado del pago se ha actualizado a: Pagado');

        $this->assertDatabaseHas('pedidos', [
            'id' => $pedidoId,
            'estado_pago' => 'Pagado',
        ]);
        $this->assertDatabaseHas('pedido_historials', [
            'orden_id' => $pedidoId,
            'nota' => 'El administrador verificó en la app de pago el abono y confirmó el comprobante de Yape.',
        ]);
    }

    public function test_admin_cannot_advance_a_manual_order_while_payment_is_pending(): void
    {
        $admin = $this->paymentAdmin();
        $pedidoId = $this->createPendingManualPayment();

        $this->actingAs($admin)
            ->from(route('admin.pedidos.create', $pedidoId))
            ->post(route('admin.pedidos.avanzar', $pedidoId))
            ->assertRedirect(route('admin.pedidos.create', $pedidoId))
            ->assertSessionHas('mensaje', 'No se puede avanzar el pedido: primero confirma que la transferencia fue recibida.');

        $this->assertDatabaseHas('pedidos', [
            'id' => $pedidoId,
            'estado' => 'PENDIENTE',
            'estado_pago' => 'Pendiente',
        ]);
        $this->assertDatabaseCount('pedido_historials', 0);
    }

    public function test_the_confirmation_page_offers_a_dropzone_for_the_payment_receipt(): void
    {
        $pedidoId = $this->createPendingManualPayment('pendiente', false);
        $usuarioGalyId = \Illuminate\Support\Facades\DB::connection('galy')
            ->table('usuarios')
            ->where('email', 'cliente-pago@example.test')
            ->value('id');
        $cliente = User::factory()->create(['galy_user_id' => $usuarioGalyId]);

        $this->actingAs($cliente)
            ->get(route('web.pedidos.confirmacion', $pedidoId))
            ->assertOk()
            ->assertSee('¡Pedido enviado!')
            ->assertSee('Método de pago')
            ->assertSee('Ver mis pedidos')
            ->assertSee('id="receipt-dropzone"', false)
            ->assertSee('Subir comprobante')
            ->assertSee('Comprobante de pago');
    }

    public function test_la_confirmacion_deja_agrandar_el_qr_y_copiar_el_importe_exacto(): void
    {
        \Illuminate\Support\Facades\DB::table('ajustes')->insert([
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
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pedidoId = $this->createPendingManualPayment('pendiente', false);
        \Illuminate\Support\Facades\DB::table('pedidos')->where('id', $pedidoId)->update([
            'direccion' => 'Alias: casa | Dirección: jr cristo rey 123 | Ref: frente al parque | Provincia: N/A | País: Perú',
        ]);

        $usuarioGalyId = \Illuminate\Support\Facades\DB::connection('galy')
            ->table('usuarios')
            ->where('email', 'cliente-pago@example.test')
            ->value('id');
        $cliente = User::factory()->create(['galy_user_id' => $usuarioGalyId]);

        $this->actingAs($cliente)
            ->get(route('web.pedidos.confirmacion', $pedidoId))
            ->assertOk()
            // El QR sube como foto de pantalla: sin agrandarlo no se puede escanear.
            ->assertSee('data-qr-zoom="Yape"', false)
            ->assertSee('id="qrZoomModal"', false)
            ->assertSee('¿No lo puedes leer? Tócalo para agrandarlo.')
            // En la app del banco el importe se teclea sin miles ni símbolo.
            ->assertSee('Copiar importe')
            ->assertSee('data-copy-payment-number="129.90"', false)
            ->assertSee('data-copy-payment-number="987654321"', false)
            // La dirección legible: sin el «Alias» inventario ni el «Provincia: N/A».
            ->assertSee('jr cristo rey 123')
            ->assertSee('frente al parque')
            ->assertSee('Perú')
            ->assertDontSee('Alias: casa')
            ->assertDontSee('Provincia: N/A')
            ->assertDontSee('Dirección: jr cristo rey 123');
    }

    public function test_el_titulo_de_la_direccion_sigue_lo_que_realmente_guarda_el_pedido(): void
    {
        $pedidoId = $this->createPendingManualPayment('pendiente', false);
        $usuarioGalyId = \Illuminate\Support\Facades\DB::connection('galy')
            ->table('usuarios')
            ->where('email', 'cliente-pago@example.test')
            ->value('id');
        $cliente = User::factory()->create(['galy_user_id' => $usuarioGalyId]);

        // Un «envío a provincia» sin agencia guarda la casa del cliente: si el
        // título se elige por la modalidad, la pantalla manda a retirar una caja
        // a una agencia que nunca existió.
        $titulos = [
            'Recojo en Tienda GALY – Av. Balta 123' => 'Punto de recojo',
            'Agencia GALY Huamanga (Huamanga, Ayacucho) – jirón Lima 45' => 'Agencia de destino',
            'Alias: casa | Dirección: jr cristo rey 123' => 'Dirección de envío',
        ];

        foreach ($titulos as $texto => $tituloEsperado) {
            \Illuminate\Support\Facades\DB::table('pedidos')->where('id', $pedidoId)->update([
                'direccion' => $texto,
            ]);

            $html = $this->actingAs($cliente)
                ->get(route('web.pedidos.confirmacion', $pedidoId))
                ->assertOk()
                ->getContent();

            $this->assertStringContainsString($tituloEsperado, $html, 'El pedido decía «'.$texto.'».');

            foreach ($titulos as $otroTitulo) {
                if ($otroTitulo !== $tituloEsperado) {
                    $this->assertStringNotContainsString($otroTitulo, $html, 'El pedido decía «'.$texto.'».');
                }
            }
        }
    }

    public function test_customer_can_upload_and_only_view_their_private_payment_receipt(): void
    {
        $pedidoId = $this->createPendingManualPayment('pendiente', false);
        $usuarioGalyId = \Illuminate\Support\Facades\DB::connection('galy')
            ->table('usuarios')
            ->where('email', 'cliente-pago@example.test')
            ->value('id');
        $cliente = User::factory()->create(['galy_user_id' => $usuarioGalyId]);

        $this->actingAs($cliente)
            ->post(route('web.pedidos.comprobante', $pedidoId), [
                'comprobante' => UploadedFile::fake()->create('yape.png', 100, 'image/png'),
            ])
            ->assertRedirect(route('web.pedidos.confirmacion', $pedidoId))
            ->assertSessionHas('mensaje', 'Comprobante enviado. El pedido seguirá pendiente hasta que la tienda confirme la transferencia.');

        $this->assertDatabaseHas('pedido_pagos_manuales', [
            'pedido_id' => $pedidoId,
            'estado' => 'comprobante_enviado',
        ]);

        $this->get(route('web.pedidos.comprobante.ver', $pedidoId))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');

        $anotherCustomer = User::factory()->create(['galy_user_id' => 987654]);
        $this->actingAs($anotherCustomer)
            ->get(route('web.pedidos.comprobante.ver', $pedidoId))
            ->assertNotFound();
    }

    /**
     * El tunel publico cambia de nombre solo y el .env se queda con el viejo. La
     * tienda sigue funcionando en local, pero el cliente que paga con tarjeta
     * vuelve a una direccion que no abre y eso antes no se veia en ningun panel.
     */
    public function test_the_panel_warns_when_the_public_return_address_no_longer_resolves(): void
    {
        $this->abrirPanelDePagosConTarjeta('https://muerto-ahora.trycloudflare.com');

        Http::fake([
            'muerto-ahora.trycloudflare.com/*' => Http::failedConnection('DNS resolution error'),
        ]);

        $this->get(route('admin.pagos.index'))
            ->assertOk()
            ->assertSee('El enlace de retorno está caído')
            ->assertSee('No se perdió ningún cobro');
    }

    public function test_the_panel_shows_the_return_address_quietly_while_it_still_responds(): void
    {
        $this->abrirPanelDePagosConTarjeta('https://vivo-ahora.trycloudflare.com');

        Http::fake([
            'vivo-ahora.trycloudflare.com/*' => Http::response('ok', 302),
        ]);

        $this->get(route('admin.pagos.index'))
            ->assertOk()
            ->assertSee('https://vivo-ahora.trycloudflare.com/pago/tarjeta/retorno', false)
            ->assertDontSee('está caído');
    }

    public function test_a_return_address_pointing_at_this_pc_is_reported_as_unusable(): void
    {
        $this->abrirPanelDePagosConTarjeta('http://127.0.0.1:8000');

        // Sin tunel no hay que llamar a nadie: se sabe que no sirve leyendo el host.
        Http::fake();

        $this->get(route('admin.pagos.index'))
            ->assertOk()
            ->assertSee('Mercado Pago no puede redirigir ahí al cliente');

        Http::assertNothingSent();
    }

    private function abrirPanelDePagosConTarjeta(string $urlBase): void
    {
        $role = Role::firstOrCreate(['name' => 'ADMINISTRADOR', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::firstOrCreate([
            'name' => 'Listado de Pedidos',
            'guard_name' => 'web',
        ]));

        $admin = User::factory()->create();
        $admin->assignRole($role);
        $this->actingAs($admin);

        config([
            'services.mercadopago.enabled' => true,
            'services.mercadopago.access_token' => 'TEST-123456-7890-abcdefghijklmnop',
            'services.mercadopago.url_base' => $urlBase,
        ]);
        Cache::forget('mercadopago.enlace-publico');
    }

    private function paymentAdmin(): User
    {
        $permission = Permission::firstOrCreate([
            'name' => 'Procesar Pedido',
            'guard_name' => 'web',
        ]);
        $role = Role::firstOrCreate([
            'name' => 'ADMINISTRADOR',
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo($permission);

        $admin = User::factory()->create();
        $admin->forceFill(['galy_user_id' => 99])->save();
        $admin->assignRole($role);

        return $admin;
    }

    private function createPendingManualPayment(string $paymentState = 'comprobante_enviado', bool $withReceipt = true): int
    {
        Storage::fake('local');

        $usuarioGalyId = \Illuminate\Support\Facades\DB::connection('galy')->table('usuarios')->insertGetId([
            'nombre' => 'Cliente de prueba',
            'email' => 'cliente-pago@example.test',
            'password' => 'hashed-password',
        ]);
        $pedidoId = \Illuminate\Support\Facades\DB::table('pedidos')->insertGetId([
            'usuario_id' => $usuarioGalyId,
            'total' => 129.90,
            'divisa' => 'PEN',
            'estado_pago' => 'Pendiente',
            'estado' => 'PENDIENTE',
            'transaccion_id' => null,
            'metodo_entrega' => 'DOMICILIO',
            'direccion' => 'Dirección de prueba',
            'cliente_nombre' => 'Cliente de prueba',
            'cliente_email' => 'cliente-pago@example.test',
            'metodo_pago' => 'yape',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $receiptPath = $withReceipt ? "comprobantes-pago/receipt-{$pedidoId}.png" : null;
        if ($receiptPath) {
            Storage::disk('local')->put($receiptPath, 'test receipt');
        }
        \Illuminate\Support\Facades\DB::table('pedido_pagos_manuales')->insert([
            'pedido_id' => $pedidoId,
            'usuario_id' => $usuarioGalyId,
            'metodo_pago' => 'yape',
            'comprobante_path' => $receiptPath,
            'estado' => $paymentState,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $pedidoId;
    }

    private function createCardPayment(
        bool $withAttempt = true,
        string $attemptState = IntentoPagoMercadoPago::ESTADO_ESPERANDO,
        ?string $gatewayState = null,
        ?string $gatewayDetail = null,
    ): int {
        $usuarioGalyId = \Illuminate\Support\Facades\DB::connection('galy')->table('usuarios')->insertGetId([
            'nombre' => 'Cliente con tarjeta',
            'email' => 'cliente-tarjeta'.uniqid().'@example.test',
            'password' => 'hashed-password',
        ]);

        $pedidoId = \Illuminate\Support\Facades\DB::table('pedidos')->insertGetId([
            'usuario_id' => $usuarioGalyId,
            'total' => 129.90,
            'divisa' => 'PEN',
            'estado_pago' => 'Pendiente',
            'estado' => 'PENDIENTE',
            'metodo_entrega' => 'RECOJO',
            'direccion' => 'Tienda GALY',
            'cliente_nombre' => 'Cliente con tarjeta',
            'cliente_email' => 'cliente-tarjeta@example.test',
            'metodo_pago' => 'tarjeta',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (! $withAttempt) {
            return $pedidoId;
        }

        \Illuminate\Support\Facades\DB::table('intentos_pago_mercadopago')->insert([
            'referencia_externa' => (string) Str::uuid(),
            'usuario_id' => $usuarioGalyId,
            'firma_carrito' => str_repeat('f', 64),
            'monto' => 129.90,
            'divisa' => 'PEN',
            'estado' => $attemptState,
            'mercadopago_order_id' => 'MP-ORDER-'.$pedidoId,
            'mercadopago_payment_id' => 'MP-PAY-'.$pedidoId,
            'estado_mercadopago' => $gatewayState,
            'detalle_estado_mercadopago' => $gatewayDetail,
            'pedido_id' => $pedidoId,
            'direccion_envio' => 'Tienda GALY',
            'modalidad_entrega' => 'recojo',
            'items' => json_encode([['nombre' => 'Producto', 'cantidad' => 1]]),
            'checkout_url' => 'https://sandbox.mercadopago.com/checkout/primicia',
            'expira_at' => now()->addMinutes(30),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $pedidoId;
    }

    private function createLegacyPayment(): int
    {
        $pedidoId = \Illuminate\Support\Facades\DB::table('pedidos')->insertGetId([
            'usuario_id' => null,
            'total' => 50,
            'divisa' => 'PEN',
            'estado_pago' => 'Pendiente',
            'estado' => 'PENDIENTE',
            'transaccion_id' => null,
            'metodo_entrega' => 'DOMICILIO',
            'direccion' => 'Pedido anterior',
            'metodo_pago' => 'YAPE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $pedidoId;
    }
}
