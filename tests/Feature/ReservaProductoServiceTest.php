<?php

namespace Tests\Feature;

use App\Services\ReservaProductoService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReservaProductoServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.galy' => config('database.connections.sqlite')]);
        DB::purge('galy');

        Schema::connection('galy')->create('productos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('stock')->default(0);
        });
        Schema::connection('galy')->create('reservas_productos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->unsignedBigInteger('producto_id');
            $table->unsignedInteger('cantidad');
            $table->string('estado', 20)->default('activa');
            $table->dateTime('expira_at');
            $table->unsignedBigInteger('pedido_id')->nullable();
            $table->timestamps();
        });
        DB::connection('galy')->table('productos')->insert(['id' => 7, 'stock' => 5]);
    }

    public function test_cancelling_a_reservation_restores_stock_only_once(): void
    {
        $service = new ReservaProductoService;
        $reservationId = $service->reserve(12, 7, 2);

        $this->assertSame(3, (int) DB::connection('galy')->table('productos')->where('id', 7)->value('stock'));
        $this->assertTrue($service->cancel($reservationId, 12));
        $this->assertFalse($service->cancel($reservationId, 12));
        $this->assertSame(5, (int) DB::connection('galy')->table('productos')->where('id', 7)->value('stock'));
    }

    public function test_expiring_a_reservation_restores_its_inventory(): void
    {
        $service = new ReservaProductoService;
        $reservationId = $service->reserve(12, 7, 2);
        $expiry = DB::connection('galy')->table('reservas_productos')->where('id', $reservationId)->value('expira_at');
        $this->travelTo(\Illuminate\Support\Carbon::parse($expiry)->addSecond());

        $this->assertSame(1, $service->expireDueReservations());
        $this->assertSame(0, $service->expireDueReservations());
        $this->assertSame(5, (int) DB::connection('galy')->table('productos')->where('id', 7)->value('stock'));
    }

    public function test_order_consumes_reserved_units_without_deducting_them_twice(): void
    {
        DB::connection('galy')->table('productos')->where('id', 7)->update(['stock' => 8]);
        $service = new ReservaProductoService;
        $reservationId = $service->reserve(12, 7, 3);

        $service->consumeForOrder(12, 7, 5, 91);

        $reservation = DB::connection('galy')->table('reservas_productos')->where('id', $reservationId)->first();
        $this->assertSame('convertida', $reservation->estado);
        $this->assertSame(91, (int) $reservation->pedido_id);
        $this->assertSame(3, (int) DB::connection('galy')->table('productos')->where('id', 7)->value('stock'));
    }
}
