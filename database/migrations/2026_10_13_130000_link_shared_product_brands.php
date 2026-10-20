<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->environment('testing') || ! Schema::connection('galy')->hasTable('marcas')) {
            return;
        }

        $connection = DB::connection('galy');
        $brands = $connection->table('marcas')->orderBy('id')->get(['id', 'nombre', 'slug']);

        foreach ($connection->table('productos')->whereNull('marca_id')->whereNotNull('marca')->get(['id', 'marca']) as $product) {
            $brandName = trim((string) $product->marca);
            if ($brandName === '') {
                continue;
            }

            $brand = $brands->first(
                fn (object $candidate): bool => mb_strtolower(trim((string) $candidate->nombre)) === mb_strtolower($brandName)
            );

            if (! $brand) {
                $baseSlug = Str::slug($brandName);
                $slug = $baseSlug !== '' ? $baseSlug : 'marca';
                $suffix = 2;
                while ($brands->contains(fn (object $candidate): bool => $candidate->slug === $slug)) {
                    $slug = ($baseSlug !== '' ? $baseSlug : 'marca').'-'.$suffix++;
                }

                $brandId = $connection->table('marcas')->insertGetId([
                    'nombre' => $brandName,
                    'slug' => $slug,
                    'imagen' => null,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $brand = (object) ['id' => $brandId, 'nombre' => $brandName, 'slug' => $slug];
                $brands->push($brand);
            }

            $connection->table('productos')
                ->where('id', $product->id)
                ->whereNull('marca_id')
                ->update(['marca_id' => $brand->id]);
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Los vínculos y marcas compartidos pueden estar en uso y no se revierten automáticamente.');
    }
};
