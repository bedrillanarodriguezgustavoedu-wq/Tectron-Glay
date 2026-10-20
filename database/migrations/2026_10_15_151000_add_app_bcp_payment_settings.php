<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las columnas ya existían en la base de datos heredada, así que se agregan
     * solo las que falten para que la migración se pueda registrar sin romper nada.
     */
    public function up(): void
    {
        $nuevas = array_values(array_diff(
            ['app_bcp_cci', 'app_bcp_cuenta', 'app_bcp_titular'],
            Schema::getColumnListing('ajustes'),
        ));

        if ($nuevas === []) {
            return;
        }

        Schema::table('ajustes', function (Blueprint $table) use ($nuevas): void {
            if (in_array('app_bcp_cci', $nuevas, true)) {
                $table->string('app_bcp_cci', 30)->nullable();
            }
            if (in_array('app_bcp_cuenta', $nuevas, true)) {
                $table->string('app_bcp_cuenta', 30)->nullable();
            }
            if (in_array('app_bcp_titular', $nuevas, true)) {
                $table->string('app_bcp_titular', 120)->nullable();
            }
        });
    }

    public function down(): void
    {
        $existentes = array_values(array_intersect(
            ['app_bcp_cci', 'app_bcp_cuenta', 'app_bcp_titular'],
            Schema::getColumnListing('ajustes'),
        ));

        if ($existentes === []) {
            return;
        }

        Schema::table('ajustes', function (Blueprint $table) use ($existentes): void {
            $table->dropColumn($existentes);
        });
    }
};