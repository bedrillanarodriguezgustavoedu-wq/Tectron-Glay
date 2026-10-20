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
        Schema::table('ajustes', function (Blueprint $table) {
            $table->string('menu_bg_color', 7)->default('#ffffff');
            $table->string('menu_text_color', 7)->default('#475569');
            $table->string('menu_hover_color', 7)->default('#0d6efd');
            $table->string('menu_active_color', 7)->default('#0d6efd');
            $table->string('menu_hover_bg_color', 7)->default('#f1f5f9');
            $table->string('menu_icon_color', 7)->default('#475569');
            $table->string('menu_active_border_color', 7)->default('#0d6efd');
            $table->decimal('menu_opacity', 3, 2)->default(1.00);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ajustes', function (Blueprint $table) {
            $table->dropColumn([
                'menu_bg_color',
                'menu_text_color',
                'menu_hover_color',
                'menu_active_color',
                'menu_hover_bg_color',
                'menu_icon_color',
                'menu_active_border_color',
                'menu_opacity'
            ]);
        });
    }
};
