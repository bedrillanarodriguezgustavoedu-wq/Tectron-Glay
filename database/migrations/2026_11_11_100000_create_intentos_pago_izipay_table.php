<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cada intento de pago con Izipay guarda el `formToken` que devolvió la API.
 * El token solo sirve para dibujar el formulario: el dinero se mueve cuando el
 * cliente paga, y ahí se confirma el pedido con la firma HMAC de `kr-answer`.
 */
return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('galy');

        if ($schema->hasTable('intentos_pago_izipay')) {
            return;
        }

        $schema->create('intentos_pago_izipay', function (Blueprint $table): void {
            $table->id();
            // `orderId` que viaja a Izipay; vuelve en `orderDetails.orderId`.
            $table->uuid('referencia')->unique();
            $table->unsignedBigInteger('usuario_id')->index();
            $table->unsignedBigInteger('pedido_id')->index();
            $table->decimal('monto', 12, 2);
            $table->string('divisa', 10);
            $table->string('estado', 24)->default('iniciando')->index();
            $table->text('form_token')->nullable();
            $table->string('izipay_uuid')->nullable()->unique();
            $table->string('estado_izipay', 40)->nullable();
            $table->string('detalle_estado_izipay', 120)->nullable();
            $table->string('metodo_izipay', 40)->nullable();
            $table->dateTime('expira_at')->nullable()->index();
            $table->dateTime('pagado_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('galy')->dropIfExists('intentos_pago_izipay');
    }
};
