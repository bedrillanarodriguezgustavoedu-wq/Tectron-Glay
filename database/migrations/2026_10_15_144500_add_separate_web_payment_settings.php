<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ajustes', function (Blueprint $table): void {
            $table->string('web_yape_numero', 30)->nullable();
            $table->string('web_yape_titular', 120)->nullable();
            $table->string('web_yape_qr')->nullable();
            $table->string('web_plin_numero', 30)->nullable();
            $table->string('web_plin_titular', 120)->nullable();
            $table->string('web_plin_qr')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ajustes', function (Blueprint $table): void {
            $table->dropColumn([
                'web_yape_numero',
                'web_yape_titular',
                'web_yape_qr',
                'web_plin_numero',
                'web_plin_titular',
                'web_plin_qr',
            ]);
        });
    }
};
