<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('ajustes', 'web_mercadopago_verificado_at')) {
            return;
        }

        Schema::table('ajustes', function (Blueprint $table): void {
            $table->string('web_mercadopago_token_hash', 64)->nullable();
            $table->timestamp('web_mercadopago_verificado_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ajustes', function (Blueprint $table): void {
            $table->dropColumn(['web_mercadopago_token_hash', 'web_mercadopago_verificado_at']);
        });
    }
};
