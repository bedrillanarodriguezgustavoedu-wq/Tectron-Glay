<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('galy');

        if ($schema->hasTable('intentos_pago_mercadopago')) {
            // Un pedido puede reabrir el checkout varias veces, así que pedido_id no
            // puede ser único.
            if ($schema->hasIndex('intentos_pago_mercadopago', 'intentos_pago_mercadopago_pedido_id_unique')) {
                $schema->table('intentos_pago_mercadopago', function (Blueprint $table): void {
                    $table->dropUnique('intentos_pago_mercadopago_pedido_id_unique');
                    $table->index('pedido_id');
                });
            }

            return;
        }

        $schema->create('intentos_pago_mercadopago', function (Blueprint $table): void {
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

    public function down(): void
    {
        Schema::connection('galy')->dropIfExists('intentos_pago_mercadopago');
    }
};
