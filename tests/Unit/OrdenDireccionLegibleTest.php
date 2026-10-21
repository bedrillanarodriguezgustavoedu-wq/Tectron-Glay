<?php

namespace Tests\Unit;

use App\Models\Orden;
use Tests\TestCase;

/**
 * La columna heredada `direccion` guarda el texto que escribieron la web, la app
 * Android y el panel. Para la tienda está bien, pero en la pantalla del cliente
 * sale un muro del tipo «Alias: casa | Dirección: jr cristo rey | País: Perú» que
 * repite la calle y mete campos vacíos. Aquí se prueba solo el filtrado.
 */
class OrdenDireccionLegibleTest extends TestCase
{
    private function lineas(string $texto): array
    {
        return (new Orden())->forceFill(['direccion' => $texto])->direccion_envio_lineas();
    }

    public function test_se_quitan_los_rotulos_y_los_campos_vacios(): void
    {
        $this->assertSame(
            ['jr cristo rey 123', 'frente al parque', 'Lambayeque', 'Chiclayo'],
            $this->lineas('Alias: casa | Dirección: jr cristo rey 123 | Ref: frente al parque | Provincia: Lambayeque | Departamento: Chiclayo | País: | Telf: ')
        );
    }

    public function test_el_alias_no_es_direccion_y_no_se_repiten_los_valores(): void
    {
        // «casa» es el apodo con el que el cliente guardó la dirección, no el lugar
        // donde toca timbrar; y el mismo texto puede venir repetido por formato.
        $this->assertSame(
            ['Av. Always Never 742'],
            $this->lineas('Alias: casa | Dirección: Av. Always Never 742 | Direccion: Av. Always Never 742')
        );
    }

    public function test_una_direccion_sin_provincia_no_devuelve_na(): void
    {
        $this->assertSame(
            ['jr san Martín 456'],
            $this->lineas('Alias: casa | Dirección: jr san Martín 456 | Provincia: N/A')
        );
    }

    public function test_el_texto_de_un_punto_de_recojo_se_conserva_como_esta(): void
    {
        // En recojo la columna trae el nombre de la tienda o la agencia: no tiene
        // rotulos que limpiar y es justamente lo que el cliente necesita leer.
        $this->assertSame(
            ['Recojo en Tienda'],
            $this->lineas('Recojo en Tienda')
        );
        $this->assertSame(
            ['Agencia GALY Chiclayo — cruce con Av. Balta'],
            $this->lineas('Agencia GALY Chiclayo — cruce con Av. Balta')
        );
    }

    public function test_una_direccion_vacia_no_devuelve_lineas(): void
    {
        $this->assertSame([], $this->lineas(''));
        $this->assertSame([], $this->lineas('Alias: casa'));
    }
}
