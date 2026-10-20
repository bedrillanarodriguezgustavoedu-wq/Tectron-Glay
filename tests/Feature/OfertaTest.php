<?php

namespace Tests\Feature;

use App\Models\Oferta;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OfertaTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_shared_offer_refreshes_prices_even_when_tectron_product_map_is_empty(): void
    {
        $this->assertDatabaseCount('tectron_galy_product_map', 0);

        $product = Producto::factory()->create([
            'precio_venta' => 100,
            'precio_oferta' => 100,
            'precio_oferta_manual' => null,
            'descuento' => null,
            'en_oferta' => false,
        ]);
        $manualProduct = Producto::factory()->create([
            'precio_venta' => 200,
            'precio_oferta' => 180,
            'precio_oferta_manual' => 180,
            'descuento' => 10,
            'en_oferta' => true,
        ]);

        $offer = Oferta::create([
            'nombre' => 'Oferta de prueba',
            'tipo_descuento' => 'porcentaje',
            'valor_descuento' => 10,
            'fecha_inicio' => now()->subMinute(),
            'fecha_fin' => now()->addHour(),
            'activo' => true,
        ]);
        $offer->productos()->attach($product->id);

        Oferta::refreshProductPrices();

        $this->assertDatabaseHas('productos', [
            'id' => $product->id,
            'precio_oferta' => '90.00',
            'descuento' => 10,
            'en_oferta' => true,
        ]);
        $this->assertDatabaseHas('productos', [
            'id' => $manualProduct->id,
            'precio_oferta' => '180.00',
            'descuento' => 10,
            'en_oferta' => true,
        ]);

        $offer->update(['fecha_fin' => now()->subMinute()]);
        Oferta::refreshProductPrices();

        $this->assertDatabaseHas('productos', [
            'id' => $product->id,
            'precio_oferta' => '100.00',
            'descuento' => null,
            'en_oferta' => false,
        ]);
        $this->assertSame(2, DB::table('productos')->count());
    }
}
