<?php

namespace Tests\Feature\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Las tablas del canal GALY (pedidos, pedido_productos, pedido_pagos_manuales) no nacen
 * de las migraciones y, en pruebas, la conexión "galy" es una base sqlite distinta a la
 * de los modelos. Este trait recrea ambas vistas para poder probar pagos y pedidos.
 */
trait CreatesOrderPaymentTables
{
    protected function crearTablasDePagos(): void
    {
        Schema::create('producto_imagenes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->string('imagen')->nullable();
            $table->timestamps();
        });

        Schema::create('pedidos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->decimal('total', 10, 2);
            $table->timestamp('fecha')->nullable();
            $table->string('estado')->nullable();
            $table->unsignedBigInteger('stock_reserved')->default(0);
            $table->string('metodo_pago')->nullable();
            $table->string('metodo_entrega')->nullable();
            $table->string('cliente_email')->nullable();
            $table->string('cliente_dni')->nullable();
            $table->string('cliente_telefono')->nullable();
            $table->text('direccion')->nullable();
            $table->string('ciudad')->nullable();
            $table->string('distrito')->nullable();
            $table->string('referencia')->nullable();
            $table->string('maps_url')->nullable();
            $table->string('divisa')->nullable();
            $table->string('estado_pago')->nullable();
            $table->string('estado_orden')->nullable();
            $table->string('transaccion_id')->nullable();
            $table->string('direccion_envio')->nullable();
            $table->string('modalidad_entrega')->nullable();
            $table->unsignedBigInteger('direccion_id')->nullable();
            $table->text('nota')->nullable();
            $table->string('cliente_nombre')->nullable();
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

        Schema::dropIfExists('pedido_historials');
        Schema::create('pedido_historials', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('orden_id');
            $table->string('estado_anterior')->nullable();
            $table->string('estado_nuevo')->nullable();
            $table->date('fecha');
            $table->time('hora');
            $table->text('nota')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->timestamps();
        });

        $this->crearTablaIntentosMercadoPago();

        Schema::create('pedido_pagos_manuales', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pedido_id')->unique();
            $table->unsignedBigInteger('usuario_id')->index();
            $table->string('metodo_pago', 20);
            $table->string('comprobante_path')->nullable();
            $table->string('numero_operacion', 60)->nullable();
            $table->string('estado', 30)->default('pendiente');
            $table->timestamp('comprobante_enviado_at')->nullable();
            $table->timestamp('confirmado_at')->nullable();
            $table->unsignedBigInteger('confirmado_por')->nullable();
            $table->timestamps();
        });

        Schema::connection('galy')->create('reservas_productos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('usuario_id')->index();
            $table->unsignedBigInteger('producto_id')->index();
            $table->unsignedInteger('cantidad');
            $table->string('estado', 20)->default('activa')->index();
            $table->dateTime('expira_at')->index();
            $table->unsignedBigInteger('pedido_id')->nullable()->index();
            $table->timestamps();
        });
    }

    protected function crearTablaIntentosMercadoPago(): void
    {
        Schema::create('intentos_pago_mercadopago', function (Blueprint $table): void {
            $table->uuid('referencia_externa')->primary();
            $table->unsignedBigInteger('usuario_id')->index();
            $table->char('firma_carrito', 64)->index();
            $table->decimal('monto', 12, 2);
            $table->string('divisa', 10);
            $table->string('estado', 24)->default('iniciando')->index();
            $table->string('mercadopago_order_id')->nullable()->unique();
            $table->string('mercadopago_payment_id')->nullable()->unique();
            $table->string('estado_mercadopago', 50)->nullable();
            $table->string('detalle_estado_mercadopago', 80)->nullable();
            $table->unsignedBigInteger('pedido_id')->nullable()->index();
            $table->unsignedBigInteger('direccion_id')->nullable();
            $table->text('direccion_envio');
            $table->string('modalidad_entrega', 30);
            $table->string('opcion_envio', 20)->nullable();
            $table->json('items');
            $table->json('reserva_ids')->nullable();
            $table->text('checkout_url')->nullable();
            $table->dateTime('expira_at')->index();
            $table->dateTime('confirmado_at')->nullable();
            $table->timestamps();
        });
    }
}
