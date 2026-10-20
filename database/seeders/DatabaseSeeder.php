<?php

namespace Database\Seeders;

use App\Models\Ajuste;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        if (!User::where('email', 'admin@gmail.com')->exists()) {
            User::create([
                'name' => 'Rafel',
                'email' => 'admin@gmail.com',
                'password' => bcrypt('12345678'),
                'galy_es_admin' => true,
            ])->assignRole('SUPER ADMIN');
        } else {
            $admin = User::where('email', 'admin@gmail.com')->first();
            $admin->galy_es_admin = true;
            $admin->save();
        }

        if (Ajuste::count() == 0) {
            Ajuste::create([
                'nombre' => 'Tectron Galy',
                'descripcion' => 'Tienda virtual de productos variados',
                'sucursal' => 'Matriz',
                'direccion' => 'Av. Siempre Viva 123',
                'telefono' => '0998765432',
                'logo' => 'logos/tectron-galy.jpg',
                'imagen_login' => 'imagenes_login/J4cU8Ikbk71ioPp6U3dgJiQbyOy8UwRbrNxlFHb1.jpg',
                'email' => 'tecentron@gmail.com',
                'divisa' => 'PEN',
                'pagina_web' => 'www.tectrongaly.com',
            ]);
        }

        // Seed brands first
        $this->call(MarcaSeeder::class);

        // Seed categories directly
        $categoriesData = [
            ['nombre' => 'Laptops', 'slug' => 'laptops', 'icono' => 'bi-laptop', 'descripcion' => 'Laptops y portátiles de alto rendimiento'],
            ['nombre' => 'Computadoras', 'slug' => 'computadoras', 'icono' => 'bi-pc-display', 'descripcion' => 'Equipos de escritorio, PCs armadas y todo en uno'],
            ['nombre' => 'Componentes', 'slug' => 'componentes', 'icono' => 'bi-cpu', 'descripcion' => 'Procesadores, tarjetas de video, placas madre y más'],
            ['nombre' => 'Monitores', 'slug' => 'monitores', 'icono' => 'bi-display', 'descripcion' => 'Monitores gaming, oficina y profesionales'],
            ['nombre' => 'Impresoras', 'slug' => 'impresoras', 'icono' => 'bi-printer', 'descripcion' => 'Impresoras multifuncionales y suministros'],
            ['nombre' => 'Accesorios', 'slug' => 'accesorios', 'icono' => 'bi-keyboard', 'descripcion' => 'Teclados, mouses, audífonos y periféricos'],
            ['nombre' => 'Redes', 'slug' => 'redes', 'icono' => 'bi-wifi', 'descripcion' => 'Routers, switches y cables de red'],
            ['nombre' => 'Almacenamiento', 'slug' => 'almacenamiento', 'icono' => 'bi-device-ssd', 'descripcion' => 'Discos duros SSD, HDD y memorias USB'],
        ];

        foreach ($categoriesData as $data) {
            Categoria::firstOrCreate(['slug' => $data['slug']], $data);
        }

        // Seed products and associate images
        if (Producto::count() == 0) {
            $procesadores = ['Intel Core i3', 'Intel Core i5', 'Intel Core i7', 'Intel Ultra 7', 'AMD Ryzen 3', 'AMD Ryzen 5', 'AMD Ryzen 7', 'AMD Ryzen 9'];
            $rams = ['8 GB', '16 GB', '32 GB', '64 GB'];
            $ssds = ['256 GB SSD', '512 GB SSD', '1 TB SSD', '2 TB SSD'];
            $gpus = ['Integrada', 'NVIDIA RTX 3050', 'NVIDIA RTX 3060', 'NVIDIA RTX 4060', 'NVIDIA RTX 4070', 'NVIDIA RTX 4080', 'NVIDIA RTX 4090'];
            $pantallas = ['14"', '15.6"', '16"', '17"'];
            $sos = ['Windows 11 Home', 'Linux Ubuntu', 'Sin Sistema Operativo'];
            $colores = ['Negro', 'Blanco', 'Plata', 'Azul'];

            Producto::factory(50)->create()->each(function ($producto) use ($procesadores, $rams, $ssds, $gpus, $pantallas, $sos, $colores) {
                \App\Models\ProductoImagen::create([
                    'producto_id' => $producto->id,
                    'imagen' => 'productos/default.webp'
                ]);

                $catSlug = $producto->categoria->slug ?? '';
                if ($catSlug === 'laptops') {
                    $proc = $procesadores[array_rand($procesadores)];
                    $ram = $rams[array_rand($rams)];
                    $ssd = $ssds[array_rand($ssds)];
                    $gpu = $gpus[array_rand($gpus)];
                    $pantalla = $pantallas[array_rand($pantallas)];
                    $so = $sos[array_rand($sos)];
                    $color = $colores[array_rand($colores)];

                    $producto->detalles_tecnicos = "Procesador: {$proc} | RAM: {$ram} | Almacenamiento: {$ssd} | Tarjeta gráfica: {$gpu} | Pantalla: {$pantalla} | Sistema Operativo: {$so} | Color: {$color}";
                    $producto->save();
                } elseif ($catSlug === 'computadoras') {
                    $proc = $procesadores[array_rand($procesadores)];
                    $ram = $rams[array_rand($rams)];
                    $ssd = $ssds[array_rand($ssds)];
                    $gpu = $gpus[array_rand($gpus)];
                    $so = $sos[array_rand($sos)];
                    $color = $colores[array_rand($colores)];

                    $producto->detalles_tecnicos = "Procesador: {$proc} | RAM: {$ram} | Almacenamiento: {$ssd} | Tarjeta gráfica: {$gpu} | Sistema Operativo: {$so} | Color: {$color}";
                    $producto->save();
                }
            });
        }

        // Seed default home sections
        $secciones_home = [
            ['clave' => 'productos_destacados', 'titulo' => 'Productos Destacados', 'activo' => true, 'orden' => 1, 'limite' => 8],
            ['clave' => 'marcas', 'titulo' => 'Nuestras Marcas', 'activo' => true, 'orden' => 2, 'limite' => 8],
            ['clave' => 'ofertas', 'titulo' => 'Productos en Oferta', 'activo' => true, 'orden' => 3, 'limite' => 8],
            ['clave' => 'nuevos_productos', 'titulo' => 'Nuevos Productos', 'activo' => true, 'orden' => 4, 'limite' => 8],
            ['clave' => 'productos_recomendados', 'titulo' => 'Productos Recomendados', 'activo' => true, 'orden' => 5, 'limite' => 8],
            ['clave' => 'categorias_populares', 'titulo' => 'Categorías Populares', 'activo' => true, 'orden' => 6, 'limite' => 6],
        ];

        foreach ($secciones_home as $sec) {
            \App\Models\SeccionHome::firstOrCreate(['clave' => $sec['clave']], $sec);
        }
    }
}
