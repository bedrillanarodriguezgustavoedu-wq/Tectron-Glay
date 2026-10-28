<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('galy');
        if (! $schema->hasTable('pedidos')) {
            return;
        }

        $columnas = array_values(array_filter(
            ['cupon', 'descuento'],
            fn (string $columna): bool => ! $schema->hasColumn('pedidos', $columna)
        ));

        if ($columnas === []) {
            return;
        }

        // El descuento se guarda como monto, no como porcentaje: así el panel puede
        // cuadrar `total` contra la suma de los productos sin volver a consultar la
        // tabla de cupones, que puede cambiar o borrarse después.
        $schema->table('pedidos', function (Blueprint $table) use ($columnas): void {
            if (in_array('cupon', $columnas, true)) {
                $table->string('cupon', 20)->nullable();
            }

            if (in_array('descuento', $columnas, true)) {
                $table->decimal('descuento', 10, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('galy');
        if (! $schema->hasTable('pedidos')) {
            return;
        }

        $columnas = array_values(array_filter(
            ['cupon', 'descuento'],
            fn (string $columna): bool => $schema->hasColumn('pedidos', $columna)
        ));

        if ($columnas !== []) {
            $schema->table('pedidos', function (Blueprint $table) use ($columnas): void {
                $table->dropColumn($columnas);
            });
        }
    }
};
