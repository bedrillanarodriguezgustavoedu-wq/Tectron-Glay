<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('puntos_recojo') || Schema::hasColumn('puntos_recojo', 'provincia_id')) {
            return;
        }

        Schema::table('puntos_recojo', function (Blueprint $table): void {
            // NULL = punto propio de la tienda (Recojo en tienda).
            // Con provincia = agencia donde el cliente retira su envío a provincias.
            $table->foreignId('provincia_id')->nullable()->after('telefono')
                ->constrained('provincias')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('puntos_recojo') || ! Schema::hasColumn('puntos_recojo', 'provincia_id')) {
            return;
        }

        Schema::table('puntos_recojo', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('provincia_id');
        });
    }
};
