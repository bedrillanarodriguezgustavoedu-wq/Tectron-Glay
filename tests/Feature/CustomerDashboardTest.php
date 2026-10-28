<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('pedidos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->decimal('total', 10, 2)->default(0);
            $table->string('divisa')->default('PEN');
            $table->string('estado_pago')->default('Pendiente');
            $table->string('estado')->default('PENDIENTE');
            $table->string('transaccion_id')->nullable();
            $table->string('metodo_entrega')->default('DOMICILIO');
            $table->text('direccion')->nullable();
            $table->string('metodo_pago')->default('yape');
            $table->timestamp('fecha')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('pedido_productos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pedido_id');
            $table->unsignedBigInteger('producto_id');
            $table->unsignedInteger('cantidad')->default(1);
            $table->decimal('precio', 10, 2)->default(0);
        });

        if (! Schema::hasTable('pedido_historials')) {
            Schema::create('pedido_historials', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('orden_id');
                $table->string('estado_nuevo');
                $table->date('fecha');
                $table->time('hora');
                $table->text('nota')->nullable();
                $table->timestamps();
            });
        }

        // La tabla de favoritos es de la app Android: no la crea ninguna
        // migración de Laravel y «Cuenta» la lee para su resumen.
        Schema::create('favoritos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->unsignedBigInteger('producto_id');
            $table->timestamps();
        });
    }

    public function test_unlinked_customer_sees_empty_orders_instead_of_unassigned_orders(): void
    {
        DB::table('pedidos')->insert([
            'id' => 987,
            'usuario_id' => null,
            'created_at' => now(),
        ]);

        $customer = User::factory()->create(['galy_user_id' => null]);

        $this->actingAs($customer)
            ->get(route('web.dashboard'))
            ->assertOk()
            ->assertSee('Aún no tienes pedidos')
            ->assertSee('Cerrar sesión')
            ->assertDontSee('#ORD-987');
    }

    public function test_customer_dashboard_shows_only_their_orders_and_paginates_them(): void
    {
        $customer = User::factory()->create(['galy_user_id' => 42]);
        $otherCustomer = User::factory()->create(['galy_user_id' => 84]);

        foreach (range(1, 11) as $orderNumber) {
            DB::table('pedidos')->insert([
                'usuario_id' => 42,
                'total' => 125.50,
                'estado_pago' => 'Pagado',
                'estado' => 'ENVIADO',
                'created_at' => now()->addSeconds($orderNumber),
                'updated_at' => now(),
            ]);
        }

        DB::table('pedidos')->insert([
            'usuario_id' => 84,
            'total' => 500,
            'estado_pago' => 'Pagado',
            'estado' => 'ENTREGADO',
            'created_at' => now()->addMinute(),
            'updated_at' => now(),
        ]);

        $this->actingAs($customer)
            ->get(route('web.dashboard'))
            ->assertOk()
            ->assertSee('#ORD-11')
            ->assertDontSee('#ORD-1</span>')
            ->assertDontSee('#ORD-12')
            ->assertSee('Enviado')
            ->assertSee('Mostrando 1–10 de 11 pedidos');

        $this->get(route('web.dashboard', ['page' => 2]))
            ->assertOk()
            ->assertSee('#ORD-1')
            // El aviso de pago pendiente sigue nombrando al pedido más reciente en
            // cualquier página: lo que no puede aparecer aquí es su tarjeta.
            ->assertDontSee('#ORD-11</span>')
            ->assertDontSee('#ORD-12');
    }

    public function test_el_resumen_separa_lo_pendiente_de_pago_de_lo_entregado(): void
    {
        $customer = User::factory()->create(['galy_user_id' => 42]);

        DB::table('pedidos')->insert([
            [
                'id' => 10, 'usuario_id' => 42, 'total' => 50, 'estado_pago' => 'Pendiente',
                'estado' => 'PENDIENTE', 'created_at' => now()->subDays(3), 'updated_at' => now(),
            ],
            [
                'id' => 11, 'usuario_id' => 42, 'total' => 129.90, 'estado_pago' => 'Pendiente',
                'estado' => 'PAGADO', 'created_at' => now()->subDay(), 'updated_at' => now(),
            ],
            [
                'id' => 12, 'usuario_id' => 42, 'total' => 80, 'estado_pago' => 'Pagado',
                'estado' => 'ENTREGADO', 'created_at' => now()->subDays(5), 'updated_at' => now(),
            ],
        ]);

        $this->actingAs($customer)
            ->get(route('web.dashboard'))
            ->assertOk()
            ->assertSee('Por pagar')
            ->assertSee('Entregados')
            ->assertSee('Tienes 2 pedidos con el pago pendiente')
            // El aviso señala el más reciente de los pendientes, no el primero creado.
            ->assertSee('#ORD-11')
            ->assertSee('Ver cómo pagar');
    }

    public function test_un_estado_heredado_no_se_muestra_como_si_fuera_una_etapa_de_envio(): void
    {
        $customer = User::factory()->create(['galy_user_id' => 42]);

        DB::table('pedidos')->insert([
            'id' => 20, 'usuario_id' => 42, 'total' => 10, 'estado_pago' => 'Pagado',
            'estado' => 'PAGADO', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($customer)
            ->get(route('web.dashboard'))
            ->assertOk()
            // «PAGADO» vive en la columna de entrega por culpa del panel PHP: como
            // etapa de envío no significa nada, y el stepper se quedaba vacío.
            ->assertSee('<span class="status processing">En proceso</span>', false)
            ->assertSee('En curso');
    }

    public function test_cuenta_muestra_sus_direcciones_y_nunca_las_de_otro_cliente(): void
    {
        $customer = User::factory()->create(['galy_user_id' => 42]);
        $otraClienta = User::factory()->create(['galy_user_id' => 77]);

        $departamentoId = DB::table('departamentos')->value('id');
        $datos = [
            'pais_id' => DB::table('paises')->value('id'),
            'departamento_id' => $departamentoId,
            'provincia_id' => DB::table('provincias')->where('departamento_id', $departamentoId)->value('id'),
        ];

        DB::table('direccions')->insert($datos + [
            'usuario_id' => $customer->id, 'alias' => 'Casa',
            'direccion_exacta' => 'jr cristo rey 123', 'referencia' => 'frente al parque',
            'telefono' => '901095562', 'es_predeterminada' => true,
        ]);
        DB::table('direccions')->insert($datos + [
            'usuario_id' => $otraClienta->id, 'alias' => 'Casa de la otra persona',
            'direccion_exacta' => 'av. que no debo ver', 'es_predeterminada' => false,
        ]);

        $this->actingAs($customer)
            ->get(route('web.dashboard'))
            ->assertOk()
            ->assertSee('Direcciones guardadas')
            ->assertSee('Casa')
            ->assertSee('jr cristo rey 123')
            ->assertSee('frente al parque')
            ->assertSee('Principal')
            ->assertDontSee('Casa de la otra persona')
            ->assertDontSee('av. que no debo ver');
    }

    public function test_los_favoritos_tienen_su_bloque_en_la_cuenta(): void
    {
        $customer = User::factory()->create(['galy_user_id' => 42]);

        DB::table('favoritos')->insert([
            'usuario_id' => 42, 'producto_id' => 501, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($customer)
            ->get(route('web.dashboard'))
            ->assertOk()
            ->assertSee('Tus favoritos')
            ->assertSee('1 producto guardado')
            ->assertSee('Ver mis favoritos');
    }

    public function test_los_pedidos_no_quedan_escondidos_detras_de_una_animacion_al_hacer_scroll(): void
    {
        $customer = User::factory()->create(['galy_user_id' => 42]);

        DB::table('pedidos')->insert([
            'id' => 30, 'usuario_id' => 42, 'total' => 10, 'estado_pago' => 'Pagado',
            'estado' => 'ENVIADO', 'created_at' => now(), 'updated_at' => now(),
        ]);

        // AOS deja opacity:0 todo lo que no ha entrado por scroll. En esta pantalla
        // eso es un pago que no se ve, así que el contenido va sin data-aos.
        $this->actingAs($customer)
            ->get(route('web.dashboard'))
            ->assertOk()
            ->assertDontSee('data-aos')
            ->assertSee('<article class="order-card">', false)
            ->assertSee('<div class="account-intro">', false);
    }
}
