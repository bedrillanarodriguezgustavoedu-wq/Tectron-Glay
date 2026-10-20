<?php

namespace Database\Factories;

use App\Models\Categoria;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Categoria>
 */
class CategoriaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nombre = $this->faker->randomElement([
            'Laptops',
            'Computadoras',
            'Componentes',
            'Monitores',
            'Impresoras',
            'Accesorios',
            'Redes',
            'Almacenamiento'
        ]);

        $iconos = [
            'Laptops' => 'bi-laptop',
            'Computadoras' => 'bi-pc-display',
            'Componentes' => 'bi-cpu',
            'Monitores' => 'bi-display',
            'Impresoras' => 'bi-printer',
            'Accesorios' => 'bi-keyboard',
            'Redes' => 'bi-wifi',
            'Almacenamiento' => 'bi-device-ssd'
        ];

        return [
            'nombre' => $nombre,
            'slug' => Str::slug($nombre . '-' . $this->faker->unique()->numberBetween(1, 10000)),
            'icono' => $iconos[$nombre] ?? 'bi-tag',
            'descripcion' => $this->faker->optional(0.7)->sentence(15),
        ];
    }
}
