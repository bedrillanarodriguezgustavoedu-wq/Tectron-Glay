<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('ajustes', 'web_izipay_activa')) {
            return;
        }

        Schema::table('ajustes', function (Blueprint $table): void {
            $table->boolean('web_izipay_activa')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('ajustes', function (Blueprint $table): void {
            $table->dropColumn('web_izipay_activa');
        });
    }
};
