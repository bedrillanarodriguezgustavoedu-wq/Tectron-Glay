<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->decimal('precio_oferta_manual', 10, 2)->nullable()->after('precio_venta');
        });
        
        // Copy existing precio_oferta values into the new manual column so no existing manual pricing is lost
        if (Schema::hasColumn('productos', 'precio_oferta')) {
            DB::statement("UPDATE productos SET precio_oferta_manual = precio_oferta WHERE en_oferta = 1");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('precio_oferta_manual');
        });
    }
};
