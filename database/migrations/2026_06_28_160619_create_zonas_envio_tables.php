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
        // 1. Create paises table
        Schema::create('paises', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->boolean('activo')->default(true);
            $table->boolean('realiza_envios')->default(true);
            $table->timestamps();
        });

        // 2. Create departamentos table
        Schema::create('departamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pais_id')->constrained('paises')->onDelete('cascade');
            $table->string('nombre');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // 3. Create provincias table
        Schema::create('provincias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departamento_id')->constrained('departamentos')->onDelete('cascade');
            $table->string('nombre');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // 4. Seed geographic data for Peru
        $paisId = DB::table('paises')->insertGetId([
            'nombre' => 'Perú',
            'activo' => true,
            'realiza_envios' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $zonas = [
            'Amazonas' => ['Chachapoyas', 'Bagua', 'Bongará', 'Condorcanqui', 'Luya', 'Rodríguez de Mendoza', 'Utcubamba'],
            'Ancash' => ['Huaraz', 'Aija', 'Antonio Raymondi', 'Asunción', 'Bolognesi', 'Carhuaz', 'Carlos Fermín Fitzcarrald', 'Casma', 'Corongo', 'Huari', 'Huarmey', 'Huaylas', 'Mariscal Luzuriaga', 'Ocros', 'Pallasca', 'Pomabamba', 'Recuay', 'Santa', 'Sihuas', 'Yungay'],
            'Apurímac' => ['Abancay', 'Andahuaylas', 'Antabamba', 'Cotabambas', 'Grau', 'Chincheros', 'Aymaraes'],
            'Arequipa' => ['Arequipa', 'Camaná', 'Caravelí', 'Castilla', 'Caylloma', 'Condesuyos', 'Islay', 'La Unión'],
            'Ayacucho' => ['Huamanga', 'Cangallo', 'Huanca Sancos', 'Huanta', 'La Mar', 'Lucanas', 'Parinacochas', 'Páucar del Sara Sara', 'Sucre', 'Víctor Fajardo', 'Vilcas Huamán'],
            'Cajamarca' => ['Cajamarca', 'Cajabamba', 'Celendín', 'Chota', 'Contumazá', 'Cutervo', 'Hualgayoc', 'Jaén', 'San Ignacio', 'San Marcos', 'San Miguel', 'San Pablo', 'Santa Cruz'],
            'Callao' => ['Callao'],
            'Cusco' => ['Cusco', 'Acomayo', 'Anta', 'Calca', 'Canas', 'Canchis', 'Chumbivilcas', 'Espinar', 'La Convención', 'Paruro', 'Paucartambo', 'Quispicanchi', 'Urubamba'],
            'Huancavelica' => ['Huancavelica', 'Acobamba', 'Angaraes', 'Castrovirreyna', 'Churcampa', 'Huaytará', 'Tayacaja'],
            'Huánuco' => ['Huánuco', 'Ambo', 'Dos de Mayo', 'Huacaybamba', 'Huamalíes', 'Leoncio Prado', 'Marañón', 'Pachitea', 'Puerto Inca', 'Lauricocha', 'Yarowilca'],
            'Ica' => ['Ica', 'Chincha', 'Nazca', 'Palpa', 'Pisco'],
            'Junín' => ['Huancayo', 'Concepción', 'Chanchamayo', 'Jauja', 'Junín', 'Satipo', 'Tarma', 'Yauli', 'Chupaca'],
            'La Libertad' => ['Trujillo', 'Ascope', 'Bolívar', 'Chepén', 'Gran Chimú', 'Julcán', 'Otuzco', 'Pacasmayo', 'Pataz', 'Sánchez Carrión', 'Santiago de Chuco', 'Virú'],
            'Lambayeque' => ['Chiclayo', 'Ferreñafe', 'Lambayeque'],
            'Lima' => ['Lima', 'Barranca', 'Cajatambo', 'Canta', 'Cañete', 'Huaral', 'Huarochirí', 'Huaura', 'Oyón', 'Yauyos'],
            'Loreto' => ['Maynas', 'Alto Amazonas', 'Loreto', 'Mariscal Ramón Castilla', 'Requena', 'Ucayali', 'Datem del Marañón', 'Putumayo'],
            'Madre de Dios' => ['Tambopata', 'Manu', 'Tahuamanu'],
            'Moquegua' => ['Mariscal Nieto', 'General Sánchez Cerro', 'Ilo'],
            'Pasco' => ['Pasco', 'Daniel Alcides Carrión', 'Oxapampa'],
            'Piura' => ['Piura', 'Ayabaca', 'Huancabamba', 'Morropón', 'Paita', 'Sullana', 'Talara', 'Sechura'],
            'Puno' => ['Puno', 'Azángaro', 'Carabaya', 'Chucuito', 'El Collao', 'Huancané', 'Lampa', 'Melgar', 'Moho', 'San Antonio de Putina', 'San Román', 'Sandia', 'Yunguyo'],
            'San Martín' => ['Moyobamba', 'Bellavista', 'El Dorado', 'Huallaga', 'Lamas', 'Mariscal Cáceres', 'Picota', 'Rioja', 'San Martín', 'Tocache'],
            'Tacna' => ['Tacna', 'Candarave', 'Jorge Basadre', 'Tarata'],
            'Tumbes' => ['Tumbes', 'Contralmirante Villar', 'Zarumilla'],
            'Ucayali' => ['Coronel Portillo', 'Atalaya', 'Padre Abad', 'Purús']
        ];

        foreach ($zonas as $dptoName => $provList) {
            $dptoId = DB::table('departamentos')->insertGetId([
                'pais_id' => $paisId,
                'nombre' => $dptoName,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            foreach ($provList as $provName) {
                DB::table('provincias')->insert([
                    'departamento_id' => $dptoId,
                    'nombre' => $provName,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provincias');
        Schema::dropIfExists('departamentos');
        Schema::dropIfExists('paises');
    }
};
