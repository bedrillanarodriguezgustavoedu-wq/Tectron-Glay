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
            ->assertDontSee('#ORD-11')
            ->assertDontSee('#ORD-12');
    }
}
