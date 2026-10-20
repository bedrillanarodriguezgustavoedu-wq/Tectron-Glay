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
        Schema::create('nosotros', function (Blueprint $table) {
            $table->id();
            $table->string('titulo_principal');
            $table->string('subtitulo');
            $table->text('descripcion_principal');
            $table->text('historia');
            $table->text('mision');
            $table->text('vision');
            $table->text('valores');
            $table->string('imagen_principal')->nullable();
            $table->string('video_url')->nullable();
            $table->integer('experiencia_anos')->default(0);
            $table->integer('clientes_count')->default(0);
            $table->integer('productos_count')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Seed initial default record
        DB::table('nosotros')->insert([
            'titulo_principal' => 'Líderes en Innovación Tecnológica',
            'subtitulo' => 'Sobre Nosotros',
            'descripcion_principal' => 'Somos tu destino número uno para adquirir el mejor hardware, laptops y componentes en el país.',
            'historia' => 'Fundada con la visión de hacer la tecnología de punta accesible para todos, nos esforzamos por ofrecer un catálogo curado de las marcas líderes a nivel mundial como ASUS, MSI, Razer y Corsair. Nos enfocamos en entregar un servicio al cliente excepcional, garantía real y envíos rápidos.',
            'mision' => 'Ofrecer soluciones tecnológicas innovadoras con la mejor calidad de servicio del mercado.',
            'vision' => 'Ser el ecommerce de tecnología líder a nivel nacional, reconocidos por nuestra velocidad y seguridad.',
            'valores' => 'Innovación, Calidad, Integridad, Orientación al Cliente, Velocidad.',
            'imagen_principal' => null,
            'video_url' => null,
            'experiencia_anos' => 5,
            'clientes_count' => 1500,
            'productos_count' => 450,
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
        Schema::dropIfExists('nosotros');
    }
};
