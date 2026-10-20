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
        Schema::table('ajustes', function (Blueprint $table) {
            $table->integer('home_slider_velocidad')->default(5000);
            $table->integer('home_carrusel_velocidad')->default(3000);
            $table->integer('home_max_categorias')->default(3);
            $table->integer('home_max_productos_categoria')->default(15);
            $table->unsignedBigInteger('home_destacado_oferta_id')->nullable();
            $table->boolean('home_mostrar_contador')->default(true);

            $table->foreign('home_destacado_oferta_id')->references('id')->on('ofertas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ajustes', function (Blueprint $table) {
            $table->dropForeign(['home_destacado_oferta_id']);
            
            $table->dropColumn([
                'home_slider_velocidad',
                'home_carrusel_velocidad',
                'home_max_categorias',
                'home_max_productos_categoria',
                'home_destacado_oferta_id',
                'home_mostrar_contador',
            ]);
        });
    }
};
