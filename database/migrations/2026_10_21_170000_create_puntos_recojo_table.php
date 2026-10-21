<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('puntos_recojo')) {
            return;
        }

        Schema::create('puntos_recojo', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 100);
            $table->string('direccion', 255);
            $table->string('horario', 100)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('puntos_recojo');
    }
};
