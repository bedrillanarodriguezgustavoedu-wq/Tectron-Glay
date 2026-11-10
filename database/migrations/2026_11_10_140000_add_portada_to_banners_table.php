<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('banners', 'portada')) {
            return;
        }

        Schema::table('banners', function (Blueprint $table) {
            $table->boolean('portada')->default(false)->after('activo');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('banners', 'portada')) {
            return;
        }

        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('portada');
        });
    }
};
