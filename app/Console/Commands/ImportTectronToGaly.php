<?php

namespace App\Console\Commands;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\ProductoImagen;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ImportTectronToGaly extends Command
{
    protected $signature = 'galy:import-tectron {--apply : Import the source records; without this option the command only previews}';

    protected $description = 'Importa de forma idempotente a GALY las cuentas, categorías, marcas y productos existentes de Tectron';

    public function handle(): int
    {
        if ($this->option('apply') && app()->environment('testing')) {
            $this->components->error('La importación no se permite con APP_ENV=testing.');

            return self::FAILURE;
        }

        $mapsReady = Schema::hasTable('tectron_galy_product_map');
        if (! $mapsReady && $this->option('apply')) {
            $this->components->error('Falta la migración de conexión Tectron-GALY. Ejecuta primero las migraciones después de verificar los respaldos.');

            return self::FAILURE;
        }

        $products = DB::table('productos')->orderBy('id')->get();
        $categories = DB::table('categorias')->count();
        $brands = Schema::hasTable('marcas') ? DB::table('marcas')->count() : 0;
        $users = DB::table('users')->count();
        $mappedProducts = $mapsReady ? DB::table('tectron_galy_product_map')->count() : 0;
        $cartItems = Schema::hasTable('carritos') ? DB::table('carritos')->count() : 0;
        $favorites = Schema::hasTable('producto_favoritos') ? DB::table('producto_favoritos')->count() : 0;

        $this->line('Origen Tectron: '.$products->count().' productos, '.$categories.' categorías, '.$brands.' marcas, '.$users.' cuentas, '.$cartItems.' artículos de carrito y '.$favorites.' favoritos.');
        $this->line('Productos ya vinculados: '.$mappedProducts.'. Los pedidos nuevos se guardan directamente en GALY.');

        if (! $this->option('apply')) {
            $migrationNote = $mapsReady ? '' : ' Falta aplicar la migración antes de importar.';
            $this->components->info('Simulación terminada; no se modificó ninguna base ni archivo.'.$migrationNote.' Usa --apply para confirmar la importación.');

            return self::SUCCESS;
        }

        $this->importUsers();
        $categoryMap = $this->importCategories();
        $brandMap = $this->importBrands();
        $this->importProducts($categoryMap, $brandMap);
        $this->importSavedItems();

        $this->components->info('Importación completada. Las filas ya vinculadas no se duplican ni se sobrescriben.');

        return self::SUCCESS;
    }

    private function importUsers(): void
    {
        $galy = DB::connection('galy');
        $canonicalUsers = $galy->table('usuarios')->get(['id', 'email', 'es_admin']);

        foreach (User::query()->where('estado', true)->orderBy('id')->get() as $user) {
            if (DB::table('tectron_galy_user_map')->where('tectron_user_id', $user->id)->exists()) {
                continue;
            }

            $email = strtolower(trim((string) $user->email));
            if ($email === '') {
                throw new RuntimeException("La cuenta Tectron {$user->id} no tiene correo; no puede vincularse con GALY.");
            }

            $canonical = $canonicalUsers->first(
                fn ($row) => strtolower(trim((string) $row->email)) === $email
            );

            if ($canonical) {
                $galyUserId = (int) $canonical->id;
                $isAdmin = (bool) $canonical->es_admin;
            } else {
                $sourceIsAdmin = $user->hasRole(['SUPER ADMIN', 'ADMINISTRADOR']);
                $galyUserId = (int) $galy->table('usuarios')->insertGetId([
                    'nombre' => $user->name,
                    'email' => $email,
                    'password' => $user->getRawOriginal('password'),
                    'es_admin' => $sourceIsAdmin ? 1 : 0,
                ]);
                $isAdmin = $sourceIsAdmin;
                $canonicalUsers->push((object) [
                    'id' => $galyUserId,
                    'email' => $email,
                    'es_admin' => $isAdmin ? 1 : 0,
                ]);
            }

            DB::table('tectron_galy_user_map')->insert([
                'tectron_user_id' => $user->id,
                'galy_user_id' => $galyUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $user->galy_user_id = $galyUserId;
            $user->galy_es_admin = $isAdmin;
            $user->save();
        }
    }

    /**
     * @return array<int, int>
     */
    private function importCategories(): array
    {
        $map = [];
        $existing = Categoria::all();

        foreach (DB::table('categorias')->orderBy('id')->get() as $source) {
            $savedMap = DB::table('tectron_galy_category_map')
                ->where('tectron_category_id', $source->id)
                ->value('galy_category_id');
            if ($savedMap) {
                $map[(int) $source->id] = (int) $savedMap;
                continue;
            }

            $category = $existing->first(
                fn (Categoria $item) => mb_strtolower(trim($item->nombre)) === mb_strtolower(trim($source->nombre))
            );
            if (! $category) {
                $slug = $this->uniqueSlug((string) $source->slug, $existing);
                $category = Categoria::create([
                    'nombre' => $source->nombre,
                    'slug' => $slug,
                    'descripcion' => $source->descripcion,
                ]);
                $existing->push($category);
            }

            $map[(int) $source->id] = (int) $category->id;
            DB::table('tectron_galy_category_map')->insert([
                'tectron_category_id' => $source->id,
                'galy_category_id' => $category->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $map;
    }

    /**
     * @return array<int, int>
     */
    private function importBrands(): array
    {
        $map = [];
        if (! Schema::hasTable('marcas')) {
            return $map;
        }

        $existing = Marca::all();
        foreach (DB::table('marcas')->orderBy('id')->get() as $source) {
            $savedMap = DB::table('tectron_galy_brand_map')
                ->where('tectron_brand_id', $source->id)
                ->value('galy_brand_id');
            if ($savedMap) {
                $map[(int) $source->id] = (int) $savedMap;
                continue;
            }

            $brand = $existing->first(
                fn (Marca $item) => mb_strtolower(trim($item->nombre)) === mb_strtolower(trim($source->nombre))
            );
            if (! $brand) {
                $brand = Marca::create([
                    'nombre' => $source->nombre,
                    'slug' => $this->uniqueSlug((string) $source->slug, $existing),
                    'imagen' => $source->imagen,
                    'activo' => $source->activo ?? true,
                ]);
                $existing->push($brand);
            }

            $map[(int) $source->id] = (int) $brand->id;
            DB::table('tectron_galy_brand_map')->insert([
                'tectron_brand_id' => $source->id,
                'galy_brand_id' => $brand->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $map;
    }

    /**
     * @param array<int, int> $categoryMap
     * @param array<int, int> $brandMap
     */
    private function importProducts(array $categoryMap, array $brandMap): void
    {
        $imageRoot = rtrim((string) config('galy.images_path'), '\\/');
        if ($imageRoot === '') {
            throw new RuntimeException('GALY_IMAGES_PATH debe apuntar a la carpeta img que sirve la API y la app Android.');
        }

        $images = DB::table('producto_imagens')->orderBy('id')->get()->groupBy('producto_id');
        $sourceRoot = realpath(Storage::disk('public')->path(''));

        foreach (DB::table('productos')->orderBy('id')->get() as $source) {
            if (DB::table('tectron_galy_product_map')->where('tectron_product_id', $source->id)->exists()) {
                continue;
            }

            $code = 'TEC-'.$source->id.'-'.Str::limit((string) $source->codigo, 80, '');
            $recoveredProduct = Producto::where('codigo', $code)->first();
            if ($recoveredProduct) {
                DB::table('tectron_galy_product_map')->insert([
                    'tectron_product_id' => $source->id,
                    'galy_product_id' => $recoveredProduct->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                continue;
            }

            $categoryId = $categoryMap[(int) $source->categoria_id] ?? null;
            if (! $categoryId) {
                throw new RuntimeException("No se pudo resolver la categoría del producto Tectron {$source->id}.");
            }

            $imageFiles = [];
            foreach ($images->get($source->id, collect()) as $image) {
                $sourcePath = realpath(Storage::disk('public')->path((string) $image->imagen));
                if (! $sourcePath || ! $sourceRoot || ! str_starts_with(
                    strtolower($sourcePath),
                    strtolower($sourceRoot.DIRECTORY_SEPARATOR)
                )) {
                    throw new RuntimeException("No se encontró una imagen válida para el producto Tectron {$source->id}.");
                }

                $imageInfo = getimagesize($sourcePath);
                if ($imageInfo === false) {
                    throw new RuntimeException("No se pudo leer la imagen del producto Tectron {$source->id}.");
                }
                $extension = match ($imageInfo[2] ?? null) {
                    IMAGETYPE_JPEG => 'jpg',
                    IMAGETYPE_PNG => 'png',
                    IMAGETYPE_GIF => 'gif',
                    IMAGETYPE_WEBP => 'webp',
                    default => throw new RuntimeException("La imagen del producto Tectron {$source->id} no es un formato seguro admitido."),
                };
                $filename = 'tectron-p'.$source->id.'-i'.$image->id.'.'.$extension;
                $imageFiles[] = [$sourcePath, $filename];
            }

            $product = new Producto;
            $product->fill([
                'categoria_id' => $categoryId,
                'marca_id' => $brandMap[(int) $source->marca_id] ?? null,
                'nombre' => $source->nombre,
                'codigo' => $code,
                'descripcion_corta' => $source->descripcion_corta,
                'descripcion_larga' => $source->descripcion_larga,
                'detalles_tecnicos' => Producto::sanitizeHtml($source->detalles_tecnicos ?? ''),
                'precio_compra' => $source->precio_compra,
                'precio_venta' => $source->precio_venta,
                'precio_oferta' => $source->precio_oferta ?? null,
                'precio_oferta_manual' => $source->precio_oferta_manual ?? $source->precio_oferta ?? null,
                'descuento' => $source->descuento ?? null,
                'stock' => $source->stock,
                'destacado' => $source->destacado ?? false,
                'nuevo' => $source->nuevo ?? false,
                'recomendado' => $source->recomendado ?? false,
                'mas_vendido' => $source->mas_vendido ?? false,
                'en_oferta' => $source->en_oferta ?? false,
            ]);

            $connection = DB::connection('galy');
            $connection->transaction(function () use ($product, $imageFiles, $imageRoot, $source, $code) {
                $product->save();

                foreach ($imageFiles as [$sourcePath, $filename]) {
                    if (! is_dir($imageRoot) && ! mkdir($imageRoot, 0775, true) && ! is_dir($imageRoot)) {
                        throw new RuntimeException('No se pudo crear GALY_IMAGES_PATH.');
                    }

                    $targetPath = $imageRoot.DIRECTORY_SEPARATOR.$filename;
                    if (! is_file($targetPath) && ! copy($sourcePath, $targetPath)) {
                        throw new RuntimeException("No se pudo copiar la imagen del producto Tectron {$source->id} a GALY.");
                    }

                    ProductoImagen::create([
                        'producto_id' => $product->id,
                        'imagen' => $filename,
                    ]);
                }

                if (Schema::connection('galy')->hasColumn('productos', 'imagen_url')) {
                    $product->setAttribute('imagen_url', $imageFiles[0][1] ?? null);
                    $product->save();
                }
            });

            DB::table('tectron_galy_product_map')->insert([
                'tectron_product_id' => $source->id,
                'galy_product_id' => $product->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function importSavedItems(): void
    {
        $galy = DB::connection('galy');
        $tables = [
            'carritos' => 'carrito',
            'producto_favoritos' => 'favoritos',
        ];

        foreach ($tables as $sourceTable => $targetTable) {
            if (! Schema::hasTable($sourceTable)) {
                continue;
            }

            foreach (DB::table($sourceTable)->orderBy('id')->get() as $item) {
                $userId = DB::table('tectron_galy_user_map')
                    ->where('tectron_user_id', $item->usuario_id)
                    ->value('galy_user_id');
                $productId = DB::table('tectron_galy_product_map')
                    ->where('tectron_product_id', $item->producto_id)
                    ->value('galy_product_id');
                if (! $userId || ! $productId) {
                    continue;
                }

                $exists = $galy->table($targetTable)
                    ->where('usuario_id', $userId)
                    ->where('producto_id', $productId)
                    ->exists();
                if ($exists) {
                    continue;
                }

                $values = [
                    'usuario_id' => $userId,
                    'producto_id' => $productId,
                ];
                if ($sourceTable === 'carritos') {
                    $values['cantidad'] = $item->cantidad;
                }
                if (Schema::connection('galy')->hasColumn($targetTable, 'created_at')) {
                    $values['created_at'] = $item->created_at ?? now();
                }
                if (Schema::connection('galy')->hasColumn($targetTable, 'updated_at')) {
                    $values['updated_at'] = $item->updated_at ?? now();
                }

                $galy->table($targetTable)->insert($values);
            }
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection<int, \Illuminate\Database\Eloquent\Model> $existing
     */
    private function uniqueSlug(string $desired, $existing): string
    {
        $base = Str::slug($desired) ?: 'tectron';
        $slug = $base;
        $suffix = 2;

        while ($existing->contains(fn ($item) => $item->slug === $slug)) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
