<?php

namespace Tests\Feature;

use App\Models\Asesor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AsesorComercialTest extends TestCase
{
    use RefreshDatabase;

    private function adminConPermisos(array $permisos): User
    {
        $role = Role::firstOrCreate(['name' => 'SUPER ADMIN', 'guard_name' => 'web']);

        foreach ($permisos as $nombre) {
            $role->givePermissionTo(Permission::firstOrCreate([
                'name' => $nombre,
                'guard_name' => 'web',
            ]));
        }

        $admin = User::factory()->create();
        $admin->assignRole($role);
        $this->actingAs($admin);

        return $admin;
    }

    public function test_the_public_page_lists_active_advisors_in_the_configured_order(): void
    {
        Asesor::create(['nombre' => 'Ana Quispe', 'cargo' => 'Asesora de ventas online', 'orden' => 2, 'activo' => true]);
        Asesor::create(['nombre' => 'Luis Medina', 'cargo' => 'Asesor de laptops', 'orden' => 1, 'activo' => true]);
        Asesor::create(['nombre' => 'Sofía secreta', 'orden' => 0, 'activo' => false]);

        $respuesta = $this->get('/asesores')->assertSuccessful();

        $respuesta->assertSee('Ana Quispe')
            ->assertSee('Luis Medina')
            ->assertDontSee('Sofía secreta');

        $this->assertTrue(
            mb_strpos($respuesta->getContent(), 'Luis Medina')
                < mb_strpos($respuesta->getContent(), 'Ana Quispe'),
            'El orden configurado no manda en la página pública.'
        );
    }

    public function test_a_phone_written_with_spaces_and_country_code_becomes_a_working_wa_link(): void
    {
        $asesor = Asesor::create([
            'nombre' => 'Ana Quispe',
            'whatsapp' => '+51 987 654 321',
            'activo' => true,
        ]);

        $this->assertSame('https://wa.me/51987654321', $asesor->enlace_whatsapp);
        $this->get('/asesores')->assertSee('https://wa.me/51987654321', false);
    }

    private function tarjetasDeAsesores(string $contenido): string
    {
        $inicio = mb_strpos($contenido, 'Asesores Comerciales');
        $fin = mb_strpos($contenido, '</section>', $inicio);

        $this->assertNotFalse($inicio, 'La página no renderiza el título de asesores.');
        $this->assertNotFalse($fin, 'La sección de asesores no cierra antes del pie de página.');

        return mb_substr($contenido, $inicio, $fin - $inicio);
    }

    public function test_an_advisor_without_a_number_shows_no_whatsapp_button(): void
    {
        $asesor = Asesor::create(['nombre' => 'Ana Sin Numero', 'whatsapp' => null, 'activo' => true]);

        $this->assertNull($asesor->enlace_whatsapp);

        // El pie de página siempre publica el WhatsApp de la tienda, así que el chequeo
        // se limita a la tarjeta del asesor.
        $tarjetas = $this->tarjetasDeAsesores($this->get('/asesores')->assertSuccessful()->getContent());

        $this->assertStringContainsString('Ana Sin Numero', $tarjetas);
        $this->assertStringNotContainsString('https://wa.me/', $tarjetas);
        $this->assertStringContainsString('atiende por la página de Contacto', $tarjetas);
    }

    public function test_a_number_too_short_to_dial_is_rejected_in_the_panel(): void
    {
        $this->adminConPermisos(['Listado de Asesores', 'Crear Asesor', 'Guardar Asesor']);

        $this->from('/admin/asesores/create')
            ->post('/admin/asesores/create', [
                'nombre' => 'Mal cargado',
                'whatsapp' => '987',
            ])
            ->assertSessionHasErrors('whatsapp');

        $this->assertDatabaseMissing('asesores', ['nombre' => 'Mal cargado']);
    }

    public function test_the_photo_is_stored_and_served_from_the_public_disk(): void
    {
        Storage::fake('public');
        $this->adminConPermisos(['Listado de Asesores', 'Crear Asesor', 'Guardar Asesor']);

        $this->post('/admin/asesores/create', [
            'nombre' => 'Con Foto',
            // Sin la extensión GD instalada no se puede generar una imagen falsa,
            // pero la regla `image` sí exige cabecera PNG real.
            'foto' => UploadedFile::fake()->createWithContent(
                'asesora.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/AacAAAAASUVORK5CYII=')
            ),
            'activo' => '1',
        ])->assertSessionHasNoErrors();

        $asesor = Asesor::where('nombre', 'Con Foto')->firstOrFail();

        Storage::disk('public')->assertExists($asesor->foto);
        $this->assertStringContainsString('asesores/', $asesor->foto);
    }

    public function test_an_advisor_without_a_photo_falls_back_to_initials(): void
    {
        Asesor::create(['nombre' => 'Luis Medina', 'foto' => null, 'activo' => true]);

        $asesor = Asesor::where('nombre', 'Luis Medina')->firstOrFail();

        $this->assertSame('LM', $asesor->iniciales);
        $this->assertNull($asesor->ruta_foto);
        $this->get('/asesores')->assertSee('LM');
    }

    public function test_removing_an_advisor_hides_them_but_keeps_the_record(): void
    {
        $this->adminConPermisos(['Listado de Asesores', 'Eliminar Asesor']);

        $asesor = Asesor::create(['nombre' => 'Baja temporal', 'activo' => true]);

        $this->delete('/admin/asesor/'.$asesor->id)->assertRedirect(route('admin.asesores.index'));

        $this->assertDatabaseHas('asesores', ['id' => $asesor->id, 'activo' => 0]);

        // El flash del panel se conserva en sesión y la plantilla pública lo imprime;
        // sin vaciarlo, el assertDontSee fallaría por el mensaje y no por la tarjeta.
        $this->flushSession();

        $this->get('/asesores')->assertDontSee('Baja temporal');
    }

    public function test_the_panel_warns_about_a_number_without_country_code(): void
    {
        $this->adminConPermisos(['Listado de Asesores', 'Editar Asesor']);

        $asesor = Asesor::create([
            'nombre' => 'Sin codigo de pais',
            'whatsapp' => '901095562',
            'activo' => true,
        ]);

        $this->get('/admin/asesores')
            ->assertSuccessful()
            ->assertSee('Le falta el código de país');

        $this->get('/admin/asesor/'.$asesor->id.'/edit')
            ->assertSuccessful()
            ->assertSee('https://wa.me/901095562', false);
    }

    public function test_a_customer_cannot_open_the_advisor_panel(): void
    {
        $this->adminConPermisos([]);

        $cliente = User::factory()->create();
        $cliente->assignRole(Role::firstOrCreate(['name' => 'CLIENTE', 'guard_name' => 'web']));
        $this->actingAs($cliente);

        $this->get('/admin/asesores')->assertForbidden();
        $this->post('/admin/asesores/create')->assertForbidden();
    }
}
