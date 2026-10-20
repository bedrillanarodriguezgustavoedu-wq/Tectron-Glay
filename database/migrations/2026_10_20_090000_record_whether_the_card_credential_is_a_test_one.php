<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las credenciales de la pestaña «Prueba» pueden empezar con `APP_USR-`, así
     * que el prefijo no dice si cobran de verdad. Lo que sí lo dice es la cuenta
     * que responde al probar el token, y eso hay que recordarlo entre requests.
     */
    public function up(): void
    {
        Schema::table('ajustes', function (Blueprint $table): void {
            $table->boolean('web_mercadopago_es_prueba')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('ajustes', function (Blueprint $table): void {
            $table->dropColumn('web_mercadopago_es_prueba');
        });
    }
};
