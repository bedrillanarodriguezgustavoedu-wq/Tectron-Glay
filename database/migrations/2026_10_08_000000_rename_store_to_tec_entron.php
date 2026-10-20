<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ajustes')
            ->where('nombre', 'Galy xnovatec')
            ->update(['nombre' => 'Tectron Galy']);

        DB::table('ofertas')
            ->where('nombre', 'Galy xnovatec')
            ->update(['nombre' => 'Tectron Galy']);

        DB::table('contactos')
            ->where('nombre_empresa', 'Galy xnovatec')
            ->update(['nombre_empresa' => 'Tectron Galy']);

        DB::table('footer_settings')
            ->where('nombre_empresa', 'NiceShop')
            ->update(['nombre_empresa' => 'Tectron Galy']);

        DB::table('footer_settings')
            ->where('copyright_texto', '© Copyright NiceShop. All Rights Reserved.')
            ->update(['copyright_texto' => '© Copyright Tectron Galy. All Rights Reserved.']);
    }

    public function down(): void {}
};
