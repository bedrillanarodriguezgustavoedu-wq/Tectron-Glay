<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('galy');
        if ($schema->hasTable('pedido_pagos_manuales') && ! $schema->hasColumn('pedido_pagos_manuales', 'numero_operacion')) {
            $schema->table('pedido_pagos_manuales', function (Blueprint $table): void {
                $table->string('numero_operacion', 80)->nullable();
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('galy');
        if ($schema->hasTable('pedido_pagos_manuales') && $schema->hasColumn('pedido_pagos_manuales', 'numero_operacion')) {
            $schema->table('pedido_pagos_manuales', function (Blueprint $table): void {
                $table->dropColumn('numero_operacion');
            });
        }
    }
};