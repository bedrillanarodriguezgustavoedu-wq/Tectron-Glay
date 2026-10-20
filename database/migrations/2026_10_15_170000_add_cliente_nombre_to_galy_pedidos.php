<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('galy');
        if (! $schema->hasTable('pedidos') || $schema->hasColumn('pedidos', 'cliente_nombre')) {
            return;
        }

        $schema->table('pedidos', function (Blueprint $table): void {
            $table->string('cliente_nombre')->nullable();
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('galy');
        if ($schema->hasTable('pedidos') && $schema->hasColumn('pedidos', 'cliente_nombre')) {
            $schema->table('pedidos', function (Blueprint $table): void {
                $table->dropColumn('cliente_nombre');
            });
        }
    }
};
