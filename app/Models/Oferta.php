<?php

namespace App\Models;

use App\Models\Concerns\UsesGalyConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Oferta extends Model
{
    use HasFactory;
    use UsesGalyConnection;

    protected $fillable = [
        'nombre',
        'tipo_descuento',
        'valor_descuento',
        'fecha_inicio',
        'fecha_fin',
        'activo',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'activo' => 'boolean',
    ];

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'oferta_producto');
    }

    public function getPorcentajeDescuentoAttribute()
    {
        return $this->tipo_descuento === 'porcentaje' ? (int) $this->valor_descuento : 0;
    }

    /**
     * Resets all product discount fields and recalculates them for current active offers.
     */
    public static function refreshProductPrices()
    {
        $now = now();
        $productConnectionName = (new Producto)->getConnectionName() ?? DB::getDefaultConnection();
        $productSchema = Schema::connection($productConnectionName);
        $productTable = DB::connection($productConnectionName)->table('productos');
        $managedProductIds = $productTable->pluck('id')->all();

        if ($managedProductIds === []) {
            return;
        }

        $basePriceColumn = $productSchema->hasColumn('productos', 'precio_normal') ? 'precio_normal' : 'precio_venta';
        $hasOfferField = $productSchema->hasColumn('productos', 'precio_oferta');
        $hasDiscountField = $productSchema->hasColumn('productos', 'descuento');
        $hasEnOfertaField = $productSchema->hasColumn('productos', 'en_oferta');
        $hasManualOfferField = $productSchema->hasColumn('productos', 'precio_oferta_manual');

        $updates = [];
        if ($hasOfferField) {
            $updates['precio_oferta'] = DB::raw($basePriceColumn);
        }
        if ($hasDiscountField) {
            $updates['descuento'] = null;
        }
        if ($hasEnOfertaField) {
            $updates['en_oferta'] = false;
        }

        if ($updates !== []) {
            $productTable
                ->whereIn('id', $managedProductIds)
                ->update($updates);
        }

        if ($hasManualOfferField) {
            $productTable
                ->whereIn('id', $managedProductIds)
                ->whereNotNull('precio_oferta_manual')
                ->where('precio_oferta_manual', '>', 0)
                ->update([
                    'precio_oferta' => DB::raw($basePriceColumn),
                    'en_oferta' => true,
                    'descuento' => DB::raw(
                        "CASE WHEN {$basePriceColumn} > 0 THEN ROUND((({$basePriceColumn} - precio_oferta_manual) * 100.0) / {$basePriceColumn}) ELSE NULL END"
                    ),
                ]);

            $productTable
                ->whereIn('id', $managedProductIds)
                ->whereNotNull('precio_oferta_manual')
                ->where('precio_oferta_manual', '>', 0)
                ->update([
                    'precio_oferta' => DB::raw('precio_oferta_manual'),
                ]);
        }

        // 2. Fetch all active offers within their date range
        $activeOffers = self::where('activo', true)
            ->where('fecha_inicio', '<=', $now)
            ->where('fecha_fin', '>=', $now)
            ->with(['productos' => function ($query) use ($managedProductIds) {
                $query->whereIn('productos.id', $managedProductIds);
            }])
            ->get();

        // 3. Apply the discounts
        foreach ($activeOffers as $oferta) {
            foreach ($oferta->productos as $producto) {
                $precioBase = (float) ($producto->precio_normal ?? $producto->precio_venta ?? 0);
                if ($precioBase <= 0) {
                    continue;
                }

                $descuento = 0;
                $precioOferta = $precioBase;

                if ($oferta->tipo_descuento === 'porcentaje') {
                    $descuento = (int) $oferta->valor_descuento;
                    $precioOferta = $precioBase * (1 - ($descuento / 100));
                } else {
                    $monto = (float) $oferta->valor_descuento;
                    $precioOferta = max(0, $precioBase - $monto);
                    $descuento = (int) round(($monto / $precioBase) * 100);
                }

                $producto->update([
                    'precio_oferta' => round($precioOferta, 2),
                    'descuento' => $descuento,
                    'en_oferta' => true,
                ]);
            }
        }
    }
}
