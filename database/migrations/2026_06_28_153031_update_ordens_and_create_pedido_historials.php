<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Alter ordens table to add delivery mode
        Schema::table('ordens', function (Blueprint $table) {
            $table->string('modalidad_entrega')->default('envio')->after('direccion_envio');
        });

        // 2. Create pedido_historials table
        Schema::create('pedido_historials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_id')->constrained('ordens')->onDelete('cascade');
            $table->string('estado_anterior')->nullable();
            $table->string('estado_nuevo');
            $table->date('fecha');
            $table->time('hora');
            $table->text('nota')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 3. Migrate existing orders data and seed initial history
        $orders = DB::table('ordens')->get();
        foreach ($orders as $order) {
            $initialState = 'En proceso';
            $currentState = $order->estado_orden;

            // Map old 'Procesando' status to new 'En proceso'
            if ($currentState === 'Procesando') {
                $currentState = 'En proceso';
                DB::table('ordens')->where('id', $order->id)->update(['estado_orden' => 'En proceso']);
            }

            // Create initial 'En proceso' history record
            $createdAt = $order->created_at ? new \DateTime($order->created_at) : new \DateTime();
            DB::table('pedido_historials')->insert([
                'orden_id' => $order->id,
                'estado_anterior' => null,
                'estado_nuevo' => 'En proceso',
                'fecha' => $createdAt->format('Y-m-d'),
                'hora' => $createdAt->format('H:i:s'),
                'nota' => 'Pedido registrado y en proceso de pago.',
                'usuario_id' => null,
                'created_at' => $order->created_at ?? now(),
                'updated_at' => $order->created_at ?? now(),
            ]);

            // If the order has transitioned to another state (like 'Enviado' or others)
            if ($currentState !== 'En proceso') {
                $updatedAt = $order->updated_at ? new \DateTime($order->updated_at) : new \DateTime();
                DB::table('pedido_historials')->insert([
                    'orden_id' => $order->id,
                    'estado_anterior' => 'En proceso',
                    'estado_nuevo' => $currentState,
                    'fecha' => $updatedAt->format('Y-m-d'),
                    'hora' => $updatedAt->format('H:i:s'),
                    'nota' => $order->nota ?? 'Cambio de estado automático por actualización del sistema.',
                    'usuario_id' => null, // We don't know who changed it historically
                    'created_at' => $order->updated_at ?? now(),
                    'updated_at' => $order->updated_at ?? now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_historials');

        Schema::table('ordens', function (Blueprint $table) {
            $table->dropColumn('modalidad_entrega');
        });
    }
};
