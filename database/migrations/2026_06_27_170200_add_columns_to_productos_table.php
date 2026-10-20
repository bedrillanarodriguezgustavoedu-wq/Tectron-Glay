<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->foreignId('marca_id')->nullable()->after('categoria_id')->constrained('marcas')->onDelete('set null');
            $table->decimal('precio_oferta', 10, 2)->nullable()->after('precio_venta');
            $table->integer('descuento')->nullable()->after('precio_oferta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['marca_id']);
            $table->dropColumn(['marca_id', 'precio_oferta', 'descuento']);
        });
    }
};
