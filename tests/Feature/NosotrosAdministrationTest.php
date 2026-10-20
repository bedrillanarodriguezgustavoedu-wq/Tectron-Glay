<?php

namespace Tests\Feature;

use App\Http\Controllers\NosotrosController;
use App\Http\Controllers\WebController;
use App\Models\Nosotros;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class NosotrosAdministrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('nosotros', function (Blueprint $table): void {
            $table->id();
            $table->string('titulo_principal');
            $table->string('subtitulo');
            $table->text('descripcion_principal');
            $table->text('historia');
            $table->text('mision');
            $table->text('vision');
            $table->text('valores');
            $table->string('imagen_principal')->nullable();
            $table->string('video_url')->nullable();
            $table->integer('experiencia_anos')->default(0);
            $table->integer('clientes_count')->default(0);
            $table->integer('productos_count')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
        Schema::create('ajustes', function (Blueprint $table): void {
            $table->id();
        });

        DB::table('nosotros')->insert([
            'titulo_principal' => 'Sobre GALY',
            'subtitulo' => 'Acerca de nosotros',
            'descripcion_principal' => 'Descripción inicial',
            'historia' => 'Historia inicial',
            'mision' => 'Misión inicial',
            'vision' => 'Visión inicial',
            'valores' => 'Servicio',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_saving_shared_about_content_preserves_the_web_managed_image(): void
    {
        DB::table('nosotros')->update(['imagen_principal' => 'nosotros/keep.webp']);

        $request = Request::create('/admin/nosotros', 'POST', [
            'titulo_principal' => 'Tecnología para todos',
            'subtitulo' => 'GALY',
            'descripcion_principal' => 'Contenido actualizado',
            'historia' => 'Nuestra historia',
            'mision' => 'Nuestra misión',
            'vision' => 'Nuestra visión',
            'valores' => 'Innovación, Servicio',
            'video_url' => '',
            'experiencia_anos' => 8,
            'clientes_count' => 2200,
            'productos_count' => 510,
            'activo' => '1',
        ]);

        (new NosotrosController)->store($request);

        $about = Nosotros::firstOrFail();
        $this->assertSame('Tecnología para todos', $about->titulo_principal);
        $this->assertSame('nosotros/keep.webp', $about->imagen_principal);
        $this->assertSame(8, $about->experiencia_anos);
        $this->assertTrue($about->activo);
        $this->assertSame('sqlite', $about->getConnection()->getName());
    }

    public function test_about_page_is_not_available_when_shared_content_is_inactive(): void
    {
        Nosotros::query()->update(['activo' => false]);

        $this->expectException(NotFoundHttpException::class);

        (new WebController)->nosotros();
    }
}
