<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('productos')->whereNull('categoria_id')->get(['id', 'categoria_nombre']) as $product) {
            if (! $product->categoria_nombre) {
                continue;
            }

            $categoryId = DB::table('categorias')
                ->where('nombre', $product->categoria_nombre)
                ->value('id');

            if ($categoryId !== null) {
                DB::table('productos')
                    ->where('id', $product->id)
                    ->whereNull('categoria_id')
                    ->update(['categoria_id' => $categoryId]);
            }
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Las categorías de productos compartidos pueden estar en uso y no se deben borrar automáticamente.');
    }
};
