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
            $table->boolean('review_requiere_aprobacion')->default(true);
            $table->boolean('review_permitir_imagenes')->default(true);
            $table->integer('review_max_imagenes')->default(5);
            $table->integer('review_longitud_maxima')->default(1000);
            $table->integer('review_tiempo_edicion_dias')->default(30);
            $table->boolean('review_permitir_eliminacion')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ajustes', function (Blueprint $table) {
            $table->dropColumn([
                'review_requiere_aprobacion',
                'review_permitir_imagenes',
                'review_max_imagenes',
                'review_longitud_maxima',
                'review_tiempo_edicion_dias',
                'review_permitir_eliminacion'
            ]);
        });
    }
};
