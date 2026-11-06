<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `Producto` ahora se borra suave: la columna ya existía en la base real porque
 * llegó del importado Tectron, pero la migración que la crea se salta el entorno
 * de pruebas, así que el catálogo de prueba se quedaba sin ella.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (array_unique([config('database.default'), 'galy']) as $conexion) {
            if (! Schema::connection($conexion)->hasTable('productos')
                || Schema::connection($conexion)->hasColumn('productos', 'deleted_at')) {
                continue;
            }

            Schema::connection($conexion)->table('productos', function (Blueprint $table): void {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        foreach (array_unique([config('database.default'), 'galy']) as $conexion) {
            if (! Schema::connection($conexion)->hasColumn('productos', 'deleted_at')) {
                continue;
            }

            Schema::connection($conexion)->table('productos', function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }
};
