<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $archiveTable = 'galy_previous_20261013_galy_productos';

        if (! Schema::hasTable($archiveTable)) {
            return;
        }

        $productColumns = array_flip(Schema::getColumnListing('productos'));

        foreach (DB::table($archiveTable)->orderBy('id')->get() as $archivedProduct) {
            if (DB::table('productos')->where('id', $archivedProduct->id)->exists()) {
                continue;
            }

            $product = array_intersect_key((array) $archivedProduct, $productColumns);
            DB::table('productos')->insert($product);
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Los productos restaurados ya pueden tener pedidos o cambios posteriores y no se deben borrar automáticamente.');
    }
};
