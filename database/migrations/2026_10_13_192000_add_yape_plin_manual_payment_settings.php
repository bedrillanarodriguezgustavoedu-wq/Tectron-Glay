<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ajustes', function (Blueprint $table) {
            $table->string('yape_numero', 30)->nullable();
            $table->string('yape_titular', 120)->nullable();
            $table->string('yape_qr')->nullable();
            $table->string('plin_numero', 30)->nullable();
            $table->string('plin_titular', 120)->nullable();
            $table->string('plin_qr')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ajustes', function (Blueprint $table) {
            $table->dropColumn([
                'yape_numero',
                'yape_titular',
                'yape_qr',
                'plin_numero',
                'plin_titular',
                'plin_qr',
            ]);
        });
    }
};
