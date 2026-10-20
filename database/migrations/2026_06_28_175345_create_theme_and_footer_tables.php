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
        // 1. Add theme and currency settings columns to 'ajustes' table
        Schema::table('ajustes', function (Blueprint $table) {
            $table->string('color_primario')->nullable();
            $table->string('color_secundario')->nullable();
            $table->string('color_acento')->nullable();
            $table->string('color_botones')->nullable();
            $table->string('color_enlaces')->nullable();
            $table->string('color_textos_principales')->nullable();
            $table->string('color_encabezado')->nullable();
            $table->string('color_pie_pagina')->nullable();
            $table->string('color_tarjetas')->nullable();
            $table->string('color_badges')->nullable();
            $table->string('color_etiquetas_ofertas')->nullable();
            $table->string('color_botones_carrito')->nullable();
            $table->string('color_botones_principales')->nullable();

            $table->string('moneda_nombre')->default('Sol Peruano');
            $table->string('moneda_simbolo')->default('S/.');
            $table->integer('moneda_decimales')->default(2);
            $table->string('moneda_posicion')->default('antes'); // 'antes' or 'despues'
        });

        // 2. Create 'footer_settings' table
        Schema::create('footer_settings', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_empresa')->default('NiceShop');
            $table->text('descripcion')->nullable();
            $table->string('logo')->nullable();
            $table->string('copyright_texto')->nullable();
            
            // Redes Sociales
            $table->string('facebook_link')->nullable();
            $table->string('instagram_link')->nullable();
            $table->string('tiktok_link')->nullable();
            $table->string('twitter_link')->nullable();
            $table->string('youtube_link')->nullable();
            $table->string('linkedin_link')->nullable();
            $table->string('whatsapp_link')->nullable();

            // Contacto
            $table->string('direccion')->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->text('horario_atencion')->nullable();

            // Legal
            $table->longText('terminos_condiciones_contenido')->nullable();
            $table->longText('politica_privacidad_contenido')->nullable();
            $table->longText('politica_cookies_contenido')->nullable();
            $table->longText('cambios_devoluciones_contenido')->nullable();

            // Aplicaciones
            $table->string('app_store_link')->nullable();
            $table->string('google_play_link')->nullable();

            $table->timestamps();
        });

        // 3. Create 'footer_enlaces' table
        Schema::create('footer_enlaces', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('url');
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Seed default row in 'ajustes' if they don't have theme colors yet (we set default ones)
        DB::table('ajustes')->where('id', 1)->update([
            'color_primario' => '#0d6efd',
            'color_secundario' => '#6c757d',
            'color_acento' => '#0dcaf0',
            'color_botones' => '#0d6efd',
            'color_enlaces' => '#0d6efd',
            'color_textos_principales' => '#212529',
            'color_encabezado' => '#ffffff',
            'color_pie_pagina' => '#212529',
            'color_tarjetas' => '#ffffff',
            'color_badges' => '#0d6efd',
            'color_etiquetas_ofertas' => '#dc3545',
            'color_botones_carrito' => '#0d6efd',
            'color_botones_principales' => '#0d6efd',
            'moneda_nombre' => 'Sol Peruano',
            'moneda_simbolo' => 'S/.',
            'moneda_decimales' => 2,
            'moneda_posicion' => 'antes'
        ]);

        // Seed default row in 'footer_settings'
        DB::table('footer_settings')->insert([
            'nombre_empresa' => 'NiceShop',
            'descripcion' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam in nibh vehicula, facilisis magna ut, consectetur lorem. Proin eget tortor risus.',
            'copyright_texto' => '© Copyright NiceShop. All Rights Reserved.',
            'facebook_link' => '#',
            'instagram_link' => '#',
            'tiktok_link' => '#',
            'twitter_link' => '#',
            'youtube_link' => '#',
            'direccion' => '123 Fashion Street, New York, NY 10001',
            'telefono' => '+1 (555) 123-4567',
            'email' => 'hello@example.com',
            'horario_atencion' => "Monday-Friday: 9am-6pm\nSaturday: 10am-4pm\nSunday: Closed",
            'terminos_condiciones_contenido' => '<h1>Términos y Condiciones</h1><p>Contenido por defecto de los términos y condiciones de la tienda.</p>',
            'politica_privacidad_contenido' => '<h1>Política de Privacidad</h1><p>Contenido por defecto de la política de privacidad de la tienda.</p>',
            'politica_cookies_contenido' => '<h1>Política de Cookies</h1><p>Contenido por defecto de la política de cookies de la tienda.</p>',
            'cambios_devoluciones_contenido' => '<h1>Cambios y Devoluciones</h1><p>Contenido por defecto de la política de cambios y devoluciones de la tienda.</p>',
            'app_store_link' => '#',
            'google_play_link' => '#',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Seed default links in 'footer_enlaces'
        $enlaces = [
            ['titulo' => 'New Arrivals', 'url' => '/productos', 'orden' => 1],
            ['titulo' => 'Bestsellers', 'url' => '/productos', 'orden' => 2],
            ['titulo' => 'Accessories', 'url' => '/productos', 'orden' => 3],
            ['titulo' => 'Sale', 'url' => '/ofertas', 'orden' => 4],
            ['titulo' => 'Help Center', 'url' => '/nosotros', 'orden' => 5],
            ['titulo' => 'Contact Us', 'url' => '/contacto', 'orden' => 6],
        ];

        foreach ($enlaces as $e) {
            DB::table('footer_enlaces')->insert(array_merge($e, [
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('footer_enlaces');
        Schema::dropIfExists('footer_settings');

        Schema::table('ajustes', function (Blueprint $table) {
            $table->dropColumn([
                'color_primario', 'color_secundario', 'color_acento', 'color_botones', 'color_enlaces',
                'color_textos_principales', 'color_encabezado', 'color_pie_pagina', 'color_tarjetas',
                'color_badges', 'color_etiquetas_ofertas', 'color_botones_carrito', 'color_botones_principales',
                'moneda_nombre', 'moneda_simbolo', 'moneda_decimales', 'moneda_posicion'
            ]);
        });
    }
};
