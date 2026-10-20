<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('resenas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('orden_id')->constrained('ordens')->onDelete('cascade');
            $table->integer('estrellas'); // 1 to 5
            $table->string('titulo')->nullable();
            $table->text('comentario');
            $table->boolean('aprobado')->default(false);
            $table->text('respuesta_admin')->nullable();
            $table->timestamp('fecha_respuesta')->nullable();
            $table->timestamps();

            // A customer can only write one review per purchased product inside the same order
            $table->unique(['producto_id', 'usuario_id', 'orden_id'], 'resena_unica_idx');
        });

        Schema::create('resena_imagenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resena_id')->constrained('resenas')->onDelete('cascade');
            $table->string('imagen');
            $table->integer('orden')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resena_imagenes');
        Schema::dropIfExists('resenas');
    }
};
