<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ajustes', function (Blueprint $table) {
            $table->boolean('paypal_conversion_activa')->default(false);
            $table->decimal('paypal_tasa_cambio', 10, 4)->default(1.0000);
            $table->string('paypal_divisa_destino', 10)->default('USD');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ajustes', function (Blueprint $table) {
            $table->dropColumn(['paypal_conversion_activa', 'paypal_tasa_cambio', 'paypal_divisa_destino']);
        });
    }
};
