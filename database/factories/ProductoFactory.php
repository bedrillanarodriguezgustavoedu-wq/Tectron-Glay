<?php

namespace Database\Factories;

use App\Models\Producto;
use App\Models\Categoria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Producto>
 */
class ProductoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Precios base para cálculos realistas
        $precioCompra = $this->faker->randomFloat(2, 30, 1500);
        $margenGanancia = $this->faker->randomElement([0.15, 0.20, 0.25, 0.30]); 
        $precioVenta = round($precioCompra * (1 + $margenGanancia), 2);

        // Descuentos (30% de probabilidad de tener descuento)
        $tieneDescuento = $this->faker->boolean(30);
        $descuento = $tieneDescuento ? $this->faker->randomElement([10, 15, 20, 25, 30, 40]) : null;
        $precioOferta = $tieneDescuento ? round($precioVenta * (1 - ($descuento / 100)), 2) : null;

        // Generar código único
        $codigo = 'PROD' . $this->faker->unique()->numberBetween(10000, 99999);

        // Nombres por categorías
        $nombresTech = [
            'Laptops' => [
                'Legion Pro 5', 'ROG Strix G16', 'Nitro 5', 'Victus 16', 
                'Katana 15', 'XPS 13', 'MacBook Pro 14', 'ThinkPad E14'
            ],
            'Computadoras' => [
                'PC Gamer Corsair Vengeance', 'iMac 24" M3', 'PC de Escritorio Intel Core i5',
                'Estación de Trabajo AMD Ryzen Threadripper', 'PC Gamer MSI Infinite', 'Mini PC Asus PN64'
            ],
            'Componentes' => [
                'GeForce RTX 5070 Ti', 'Ryzen 7 7800X3D', 'Core i9-14900K', 
                'Motherboard Gigabyte B650 AORUS', 'RAM G.Skill Trident Z5 32GB', 
                'Fuente Corsair RM850x Gold', 'Refrigeración Líquida MSI MAG'
            ],
            'Monitores' => [
                'UltraGear 27"', 'TUF Gaming 32" Curvo', 'Odyssey G7 28"', 
                'Optix 24" IPS', 'ASUS ProArt 27"', 'Dell UltraSharp 34" Curvo'
            ],
            'Impresoras' => [
                'EcoTank L3250', 'Pixma G3160', 'LaserJet Pro M404dw', 
                'Brother InkBenefit Tank', 'Impresora Térmica de Etiquetas'
            ],
            'Accesorios' => [
                'Mouse G Pro X Superlight', 'Teclado BlackWidow V4', 'Audífonos Cloud II', 
                'Mousepad MM300', 'Micrófono HyperX QuadCast', 'Webcam Razer Kiyo Pro'
            ],
            'Redes' => [
                'Router Archer AX55 WiFi 6', 'Switch Gigabit de 8 Puertos', 
                'Repetidor Mesh WiFi 6', 'Tarjeta de Red WiFi ASUS PCI-E'
            ],
            'Almacenamiento' => [
                'SSD Kingston NV2 1TB', 'SSD Samsung 990 PRO 2TB', 
                'Disco Externo WD Elements 2TB', 'Memoria USB SanDisk Extreme 128GB'
            ],
        ];

        // Obtener una categoría
        $categoria = Categoria::inRandomOrder()->first() ?? Categoria::factory()->create();
        $categoriaNombre = $categoria->nombre;

        // Si el nombre de la categoría no está en la lista tech, seleccionar un array genérico
        $listaNombres = $nombresTech[$categoriaNombre] ?? [
            'Producto Tech 1', 'Producto Tech 2', 'Producto Tech 3'
        ];

        $nombreBase = $this->faker->randomElement($listaNombres);
        
        // Obtener una marca
        $marca = \App\Models\Marca::inRandomOrder()->first();
        $marcaNombre = $marca ? $marca->nombre : 'Tech';
        
        $nombreCompleto = $marcaNombre . ' ' . $nombreBase . ' ' . $this->faker->randomElement(['Pro', 'Elite', 'V2', 'Slim', 'RGB', 'Edition']);

        return [
            'categoria_id' => $categoria->id,
            'marca_id' => $marca ? $marca->id : null,
            'nombre' => $nombreCompleto,
            'codigo' => $codigo,
            'descripcion_corta' => $this->faker->sentence(10),
            'descripcion_larga' => $this->faker->paragraphs(3, true),
            'precio_compra' => $precioCompra,
            'precio_venta' => $precioVenta,
            'precio_oferta' => $precioOferta,
            'descuento' => $descuento,
            'stock' => $this->faker->numberBetween(0, 50),
        ];
    }
}
