<?php

namespace Tests\Feature;

use App\Http\Controllers\CategoriaController;
use App\Models\Categoria;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CategoriaAdministrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('categorias', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->text('descripcion')->nullable();
            $table->string('icono')->nullable();
            $table->timestamps();
        });

        Schema::create('productos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('categoria_id')->nullable();
            $table->string('categoria_nombre')->nullable();
            $table->timestamps();
        });
    }

    public function test_renaming_a_category_updates_its_products_and_keeps_slug_unique(): void
    {
        DB::table('categorias')->insert([
            ['nombre' => 'Laptops', 'slug' => 'laptops', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Portátiles', 'slug' => 'portatiles', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $categoryId = (int) DB::table('categorias')->where('slug', 'portatiles')->value('id');
        DB::table('productos')->insert([
            'categoria_id' => $categoryId,
            'categoria_nombre' => 'Portátiles',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = (new CategoriaController)->update(
            Request::create('/', 'PUT', [
                'nombre' => 'Laptops',
                'slug' => 'laptops',
                'descripcion' => '',
                'icono' => '',
            ]),
            $categoryId
        );

        $this->assertSame('laptops-2', Categoria::findOrFail($categoryId)->slug);
        $this->assertSame('Laptops', DB::table('productos')->value('categoria_nombre'));
        $this->assertSame(route('admin.categorias.index'), $response->getTargetUrl());
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $categoryId = (int) DB::table('categorias')->insertGetId([
            'nombre' => 'Gaming',
            'slug' => 'gaming',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('productos')->insert([
            'categoria_id' => $categoryId,
            'categoria_nombre' => 'Gaming',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = (new CategoriaController)->destroy($categoryId);

        $this->assertDatabaseHas('categorias', ['id' => $categoryId]);
        $this->assertSame(route('admin.categorias.index'), $response->getTargetUrl());
    }

    public function test_empty_category_can_be_deleted(): void
    {
        $categoryId = (int) DB::table('categorias')->insertGetId([
            'nombre' => 'Sin productos',
            'slug' => 'sin-productos',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        (new CategoriaController)->destroy($categoryId);

        $this->assertDatabaseMissing('categorias', ['id' => $categoryId]);
    }
}
