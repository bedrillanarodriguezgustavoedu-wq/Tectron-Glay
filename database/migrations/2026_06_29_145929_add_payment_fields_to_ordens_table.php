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
        Schema::table('ordens', function (Blueprint $table) {
            $table->string('metodo_pago')->default('paypal')->after('modalidad_entrega');
            $table->string('transaccion_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordens', function (Blueprint $table) {
            $table->dropColumn('metodo_pago');
            $table->string('transaccion_id')->nullable(false)->change();
        });
    }
};
