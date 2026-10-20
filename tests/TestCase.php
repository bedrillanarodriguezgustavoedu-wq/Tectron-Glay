<?php

namespace Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.galy.driver', 'sqlite');
        config()->set('database.connections.galy.database', ':memory:');
        DB::purge('galy');

        $galySchema = Schema::connection('galy');
        if (! $galySchema->hasTable('usuarios')) {
            $galySchema->create('usuarios', function (Blueprint $table): void {
                $table->id();
                $table->string('nombre');
                $table->string('email')->unique();
                $table->string('password');
            });
        }

        if (! $galySchema->hasTable('productos')) {
            $galySchema->create('productos', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('categoria_id')->nullable();
                $table->unsignedInteger('stock')->default(0);
            });
        }

        if (! $galySchema->hasTable('pedido_productos')) {
            $galySchema->create('pedido_productos', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('producto_id');
                $table->unsignedInteger('cantidad')->default(0);
            });
        }

        if (! $galySchema->hasTable('carrito')) {
            $galySchema->create('carrito', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('usuario_id');
                $table->unsignedBigInteger('producto_id');
                $table->unsignedInteger('cantidad')->default(1);
            });
        }

        if (! Schema::hasTable('carrito')) {
            Schema::create('carrito', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('usuario_id');
                $table->unsignedBigInteger('producto_id');
                $table->unsignedInteger('cantidad')->default(1);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('productos') && ! Schema::hasColumn('productos', 'precio_normal')) {
            Schema::table('productos', function (Blueprint $table): void {
                $table->decimal('precio_normal', 10, 2)->nullable();
            });
        }

        if (Schema::hasTable('productos') && Schema::hasColumn('productos', 'precio_venta')) {
            Schema::table('productos', function (Blueprint $table): void {
                $table->decimal('precio_venta', 10, 2)->nullable()->change();
            });
        }
    }
}
