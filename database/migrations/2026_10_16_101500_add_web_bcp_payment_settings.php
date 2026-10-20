<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ajustes', function (Blueprint $table): void {
            $table->string('web_bcp_cci', 30)->nullable();
            $table->string('web_bcp_cuenta', 30)->nullable();
            $table->string('web_bcp_titular', 120)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ajustes', function (Blueprint $table): void {
            $table->dropColumn([
                'web_bcp_cci',
                'web_bcp_cuenta',
                'web_bcp_titular',
            ]);
        });
    }
};
