<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La página de asesores prometía «escríbele al asesor que atiende tu rubro», pero no
 * había ningún rubro que mostrar. Estas tres columnas son opcionales: un asesor sin
 * rubro sigue apareciendo igual que antes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('asesores')) {
            return;
        }

        Schema::table('asesores', function (Blueprint $table): void {
            if (! Schema::hasColumn('asesores', 'rubro')) {
                $table->string('rubro', 60)->nullable()->after('cargo');
            }

            if (! Schema::hasColumn('asesores', 'telefono')) {
                $table->string('telefono', 30)->nullable()->after('whatsapp');
            }

            if (! Schema::hasColumn('asesores', 'saludo')) {
                $table->string('saludo', 200)->nullable()->after('horario');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('asesores')) {
            return;
        }

        Schema::table('asesores', function (Blueprint $table): void {
            foreach (['rubro', 'telefono', 'saludo'] as $columna) {
                if (Schema::hasColumn('asesores', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });
    }
};
