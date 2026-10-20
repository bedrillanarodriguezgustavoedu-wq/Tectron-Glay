<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('galy');
        if ($schema->hasTable('reservas_productos')) {
            $requiredColumns = ['id', 'usuario_id', 'producto_id', 'cantidad', 'estado', 'expira_at', 'pedido_id', 'created_at', 'updated_at'];
            $missingColumns = array_diff($requiredColumns, $schema->getColumnListing('reservas_productos'));
            if ($missingColumns !== []) {
                throw new RuntimeException(
                    'La tabla reservas_productos ya existe pero no tiene las columnas requeridas: '.implode(', ', $missingColumns)
                );
            }

            return;
        }

        $schema->create('reservas_productos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('usuario_id')->index();
            $table->unsignedBigInteger('producto_id')->index();
            $table->unsignedInteger('cantidad');
            $table->string('estado', 20)->default('activa')->index();
            $table->dateTime('expira_at')->index();
            $table->unsignedBigInteger('pedido_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('galy')->dropIfExists('reservas_productos');
    }
};
