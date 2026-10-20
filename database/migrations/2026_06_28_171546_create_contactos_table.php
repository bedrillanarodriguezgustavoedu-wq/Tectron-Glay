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
        Schema::create('contactos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_empresa');
            $table->string('direccion');
            $table->string('pais');
            $table->string('departamento');
            $table->string('provincia');
            $table->string('telefono_principal');
            $table->string('telefono_secundario')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('email');
            $table->string('horario_atencion');
            $table->text('google_maps_link')->nullable();
            $table->string('facebook_link')->nullable();
            $table->string('instagram_link')->nullable();
            $table->string('youtube_link')->nullable();
            $table->string('twitter_link')->nullable();
            $table->text('mensaje_bienvenida')->nullable();
            $table->string('imagen_banner')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Seed initial default record
        DB::table('contactos')->insert([
            'nombre_empresa' => 'Tectron Galy',
            'direccion' => 'Av. Siempre Viva 123',
            'pais' => 'Perú',
            'departamento' => 'Lima',
            'provincia' => 'Lima',
            'telefono_principal' => '(+51) 978 104 136',
            'telefono_secundario' => null,
            'whatsapp' => '+51978104136',
            'email' => 'contacto@tienda.com',
            'horario_atencion' => 'Lunes a Viernes de 9:00 AM a 6:00 PM',
            'google_maps_link' => null,
            'facebook_link' => 'https://facebook.com',
            'instagram_link' => 'https://instagram.com',
            'youtube_link' => null,
            'twitter_link' => null,
            'mensaje_bienvenida' => 'Estamos aquí para asesorarte en tus compras de hardware y configurar la PC de tus sueños. Nuestro equipo de soporte técnico te responderá a la brevedad.',
            'imagen_banner' => null,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contactos');
    }
};
