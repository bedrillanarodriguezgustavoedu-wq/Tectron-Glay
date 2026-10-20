<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_TABLE = 'carritos';

    private const ARCHIVE_TABLE = 'carritos_legacy_20261013';

    public function up(): void
    {
        if (app()->environment('testing') || ! Schema::hasTable(self::LEGACY_TABLE)) {
            return;
        }

        if (Schema::hasTable(self::ARCHIVE_TABLE)) {
            throw new RuntimeException('La tabla de archivo del carrito ya existe; revisa ambas tablas antes de migrar.');
        }

        if (DB::table(self::LEGACY_TABLE)->exists()) {
            throw new RuntimeException('La tabla carritos contiene datos sin un mapeo seguro de usuarios GALY; no se renombró.');
        }

        Schema::rename(self::LEGACY_TABLE, self::ARCHIVE_TABLE);
    }

    public function down(): void
    {
        if (app()->environment('testing') || ! Schema::hasTable(self::ARCHIVE_TABLE)) {
            return;
        }

        if (Schema::hasTable(self::LEGACY_TABLE)) {
            throw new RuntimeException('La tabla carritos ya existe; no se puede restaurar el archivo automáticamente.');
        }

        Schema::rename(self::ARCHIVE_TABLE, self::LEGACY_TABLE);
    }
};
