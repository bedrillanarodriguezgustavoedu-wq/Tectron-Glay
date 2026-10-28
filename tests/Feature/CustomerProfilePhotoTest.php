<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\Feature\Concerns\CreatesOrderPaymentTables;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerProfilePhotoTest extends TestCase
{
    use CreatesOrderPaymentTables;
    use RefreshDatabase;

    /** Cabecera PNG real: sin la extensión GD no se puede generar una imagen falsa. */
    private function foto(string $nombre = 'yo.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $nombre,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/AacAAAAASUVORK5CYII=')
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        // «Cuenta» lee pedidos y favoritos, tablas del canal GALY que no nacen
        // de las migraciones de Laravel.
        $this->crearTablasDePagos();

        Schema::create('favoritos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->unsignedBigInteger('producto_id');
            $table->timestamps();
        });
    }

    public function test_un_cliente_puede_subir_su_foto_y_se_le_muestra_en_su_cuenta(): void
    {
        Storage::fake('public');
        $customer = User::factory()->create(['name' => 'Gus Edu']);

        $this->actingAs($customer)
            ->put('/ajustes/foto_perfil', ['foto' => $this->foto()])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $customer->refresh();

        $this->assertNotNull($customer->foto);
        $this->assertStringStartsWith('avatares/', $customer->foto);
        Storage::disk('public')->assertExists($customer->foto);

        $this->actingAs($customer)
            ->get('/ajustes')
            ->assertOk()
            ->assertSee($customer->foto, false);
    }

    public function test_el_avatar_de_la_cuenta_tambien_usa_la_foto(): void
    {
        Storage::fake('public');
        $customer = User::factory()->create(['name' => 'Gus Edu']);

        $this->actingAs($customer)->put('/ajustes/foto_perfil', ['foto' => $this->foto()]);

        $this->actingAs($customer)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee($customer->fresh()->foto, false);
    }

    public function test_un_archivo_que_no_es_una_imagen_se_rechaza_sin_guardar_nada(): void
    {
        Storage::fake('public');
        $customer = User::factory()->create(['name' => 'Gus Edu']);

        $this->actingAs($customer)
            ->put('/ajustes/foto_perfil', [
                'foto' => UploadedFile::fake()->createWithContent('notas.txt', 'hola'),
            ])
            ->assertSessionHasErrors('foto');

        $this->assertNull($customer->fresh()->foto);
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }

    public function test_un_archivo_demasiado_grande_se_rechaza(): void
    {
        Storage::fake('public');
        $customer = User::factory()->create(['name' => 'Gus Edu']);

        $this->actingAs($customer)
            ->put('/ajustes/foto_perfil', [
                'foto' => UploadedFile::fake()->create('gigante.png', 3000, 'image/png'),
            ])
            ->assertSessionHasErrors('foto');

        $this->assertNull($customer->fresh()->foto);
    }

    public function test_la_foto_nueva_reemplaza_a_la_anterior_en_el_disco(): void
    {
        Storage::fake('public');
        $customer = User::factory()->create(['name' => 'Gus Edu']);

        $this->actingAs($customer)->put('/ajustes/foto_perfil', ['foto' => $this->foto('primera.png')]);
        $primera = $customer->fresh()->foto;

        $this->actingAs($customer)->put('/ajustes/foto_perfil', ['foto' => $this->foto('segunda.png')]);
        $segunda = $customer->fresh()->foto;

        $this->assertNotSame($primera, $segunda);
        Storage::disk('public')->assertMissing($primera);
        Storage::disk('public')->assertExists($segunda);
    }

    public function test_quitar_la_foto_borra_el_archivo_y_devuelve_las_iniciales(): void
    {
        Storage::fake('public');
        $customer = User::factory()->create(['name' => 'Gus Edu']);

        $this->actingAs($customer)->put('/ajustes/foto_perfil', ['foto' => $this->foto()]);
        $foto = $customer->fresh()->foto;

        $this->actingAs($customer)
            ->delete('/ajustes/foto_perfil')
            ->assertSessionHasNoErrors();

        $this->assertNull($customer->fresh()->foto);
        Storage::disk('public')->assertMissing($foto);

        $this->actingAs($customer)
            ->get('/ajustes')
            ->assertOk()
            ->assertSee('GE');
    }

    public function test_sin_foto_el_avatar_se_resuelve_a_null_y_no_a_un_enlace_roto(): void
    {
        Storage::fake('public');
        $customer = User::factory()->create(['name' => 'Gus Edu', 'foto' => 'avatares/borrada.png']);

        $this->assertNull($customer->ruta_foto);
        $this->assertSame('GE', $customer->iniciales);
    }

    public function test_un_cliente_no_ve_la_foto_del_otro(): void
    {
        Storage::fake('public');
        $gus = User::factory()->create(['name' => 'Gus Edu']);
        $otra = User::factory()->create(['name' => 'Otra Persona']);

        $this->actingAs($gus)->put('/ajustes/foto_perfil', ['foto' => $this->foto()]);
        $fotoDeGus = $gus->fresh()->foto;

        $this->actingAs($otra)
            ->get('/ajustes')
            ->assertOk()
            ->assertDontSee($fotoDeGus, false);
    }

    public function test_quien_no_esta_logueado_no_puede_subir_ni_quitar_fotos(): void
    {
        $this->put('/ajustes/foto_perfil', ['foto' => $this->foto()])->assertRedirect('/web/login');
        $this->delete('/ajustes/foto_perfil')->assertRedirect('/web/login');
    }
}
