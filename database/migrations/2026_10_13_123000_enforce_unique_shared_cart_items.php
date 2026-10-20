<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $schema = Schema::connection('galy');
        if (! $schema->hasTable('carrito')) {
            return;
        }

        $duplicates = DB::connection('galy')
            ->table('carrito')
            ->select('usuario_id', 'producto_id')
            ->groupBy('usuario_id', 'producto_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $items = DB::connection('galy')
                ->table('carrito')
                ->where('usuario_id', $duplicate->usuario_id)
                ->where('producto_id', $duplicate->producto_id)
                ->orderBy('id')
                ->get(['id', 'cantidad']);

            $keepId = $items->first()->id;
            $totalQuantity = $items->sum('cantidad');

            DB::connection('galy')->transaction(function () use ($items, $keepId, $totalQuantity): void {
                DB::connection('galy')
                    ->table('carrito')
                    ->where('id', $keepId)
                    ->update(['cantidad' => $totalQuantity]);

                DB::connection('galy')
                    ->table('carrito')
                    ->whereIn('id', $items->skip(1)->pluck('id'))
                    ->delete();
            });
        }

        $schema->table('carrito', function (Blueprint $table): void {
            $table->unique(['usuario_id', 'producto_id'], 'carrito_usuario_producto_unique');
        });
    }

    public function down(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $schema = Schema::connection('galy');
        if ($schema->hasTable('carrito')) {
            $schema->table('carrito', function (Blueprint $table): void {
                $table->dropUnique('carrito_usuario_producto_unique');
            });
        }
    }
};
