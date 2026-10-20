<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('galy');
        if ($schema->hasTable('pedido_pagos_manuales')) {
            return;
        }

        $schema->create('pedido_pagos_manuales', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pedido_id')->unique();
            $table->unsignedBigInteger('usuario_id')->index();
            $table->string('metodo_pago', 20);
            $table->string('comprobante_path')->nullable();
            $table->string('estado', 30)->default('pendiente');
            $table->timestamp('comprobante_enviado_at')->nullable();
            $table->timestamp('confirmado_at')->nullable();
            $table->unsignedBigInteger('confirmado_por')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('galy')->dropIfExists('pedido_pagos_manuales');
    }
};
