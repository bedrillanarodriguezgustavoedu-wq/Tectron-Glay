<?php

namespace Database\Seeders;

use App\Models\Marca;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MarcaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $marcas = [
            'ASUS',
            'MSI',
            'Gigabyte',
            'Corsair',
            'Kingston',
            'Logitech',
            'HyperX',
            'Razer'
        ];

        foreach ($marcas as $nombre) {
            Marca::firstOrCreate(
                ['slug' => Str::slug($nombre)],
                [
                    'nombre' => $nombre,
                    'imagen' => 'marcas/' . Str::slug($nombre) . '.webp'
                ]
            );
        }
    }
}
