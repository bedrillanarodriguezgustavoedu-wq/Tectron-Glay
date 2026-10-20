<?php

namespace Tests\Feature;

use App\Http\Controllers\SoporteTecnicoController;
use App\Services\Galy\GalyApiClient;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SoporteTecnicoAdministrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::connection('galy')->create('servicios_tecnicos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('equipo_modelo');
            $table->text('descripcion_falla')->nullable();
            $table->string('estado')->default('Recibido');
            $table->decimal('costo_estimado', 10, 2)->nullable();
            $table->string('imagen_ingreso')->nullable();
            $table->string('imagen_listo')->nullable();
            $table->timestamp('fecha_ingreso')->nullable();
        });
        DB::connection('galy')->table('usuarios')->insert([
            'id' => 1,
            'nombre' => 'GALY',
            'email' => 'galy@example.test',
            'password' => 'unused',
        ]);
        DB::connection('galy')->table('servicios_tecnicos')->insert([
            'usuario_id' => 1,
            'equipo_modelo' => 'Equipo de prueba',
            'descripcion_falla' => 'No enciende',
            'estado' => 'Recibido',
            'costo_estimado' => 0,
            'fecha_ingreso' => now(),
        ]);
    }

    public function test_index_reads_ticket_and_customer_from_the_shared_galy_database(): void
    {
        $response = (new SoporteTecnicoController)->index();

        $this->assertSame('admin.soporte.index', $response->getName());
        $this->assertSame('Equipo de prueba', $response->getData()['tickets']->items()[0]->equipo_modelo);
        $this->assertSame('GALY', $response->getData()['tickets']->items()[0]->cliente_nombre);
    }

    public function test_ticket_updates_go_through_galy_api_to_preserve_customer_notifications(): void
    {
        config()->set('galy.api_url', 'http://localhost/galy_api');
        Http::fake([
            'http://localhost/galy_api/update_ticket_status.php' => Http::response([
                'success' => true,
                'message' => 'Estado actualizado',
            ]),
        ]);
        $request = Request::create('/admin/soporte-tecnico/1', 'PUT', [
            'estado' => 'Reparando',
            'costo' => '129.90',
        ]);

        $response = (new SoporteTecnicoController)->update($request, 1, new GalyApiClient);

        Http::assertSent(fn ($httpRequest): bool => $httpRequest->url() === 'http://localhost/galy_api/update_ticket_status.php'
            && $httpRequest['id'] === 1
            && $httpRequest['estado'] === 'Reparando'
            && $httpRequest['costo'] === 129.9);
        $this->assertSame(route('admin.soporte.index'), $response->getTargetUrl());
    }
}
