<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('banners', 'color_fondo')) {
            Schema::table('banners', function (Blueprint $table): void {
                $table->string('color_fondo', 50)->nullable();
            });
        }

        if (! Schema::hasTable('banners_inicio')) {
            return;
        }

        $source = DB::table('banners_inicio')->orderBy('id')->get();

        foreach ($source as $banner) {
            DB::table('banners')->insert([
                'imagen' => $banner->imagen_url,
                'titulo' => $banner->titulo,
                'subtitulo' => $banner->subtitulo,
                'boton_texto' => 'VER CATÁLOGO',
                'enlace' => $banner->link_url,
                'activo' => true,
                'orden' => $banner->orden,
                'fecha_publicacion' => null,
                'color_fondo' => $banner->color_fondo,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasTable('banners_inicio_legacy')) {
            Schema::rename('banners_inicio', 'banners_inicio_legacy');
        }
    }

    public function down(): void
    {
        throw new RuntimeException('La unificación de banners incluye datos compartidos y no se puede revertir automáticamente sin riesgo de pérdida.');
    }
};
