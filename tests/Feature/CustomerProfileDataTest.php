<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerProfileDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // El arnés de pruebas crea `usuarios` con cuatro columnas; el perfil
        // también sincroniza el teléfono con la app.
        if (! Schema::connection('galy')->hasColumn('usuarios', 'telefono')) {
            Schema::connection('galy')->table('usuarios', function ($table): void {
                $table->string('telefono')->nullable();
            });
        }
    }

    private function clienteVinculado(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'name' => 'Gus Edu',
            'email' => 'gus@tectron.test',
            'galy_user_id' => 7,
        ], $attributes));

        DB::connection('galy')->table('usuarios')->insert([
            'id' => 7,
            'nombre' => $user->name,
            'email' => $user->email,
            'password' => 'no-usar',
            'telefono' => null,
        ]);

        return $user;
    }

    public function test_el_cliente_puede_guardar_su_telefono_y_su_dni(): void
    {
        $user = $this->clienteVinculado();

        $this->actingAs($user)
            ->put('/ajustes/informacion_personal', [
                'name' => 'Gus Edu',
                'email' => 'gus@tectron.test',
                'telefono' => '987654321',
                'dni' => '70154892',
            ])
            ->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertSame('987654321', $user->telefono);
        $this->assertSame('70154892', $user->dni);
        $this->assertSame('987654321', DB::connection('galy')->table('usuarios')->where('id', 7)->value('telefono'));
    }

    public function test_el_dni_se_guarda_cifrado_y_no_aparece_plano_en_la_base(): void
    {
        $user = $this->clienteVinculado();

        $this->actingAs($user)->put('/ajustes/informacion_personal', [
            'name' => 'Gus Edu',
            'email' => 'gus@tectron.test',
            'dni' => '70154892',
        ]);

        $crudo = DB::table('users')->where('id', $user->id)->value('dni');

        $this->assertNotNull($crudo);
        $this->assertStringNotContainsString('70154892', (string) $crudo);
        $this->assertSame('70154892', $user->fresh()->dni);
    }

    public function test_un_dni_que_no_ocho_digitos_se_rechaza(): void
    {
        $user = $this->clienteVinculado();

        foreach (['7015489', '701548921', '7015489A', '7015 4892'] as $invalido) {
            $this->actingAs($user)
                ->put('/ajustes/informacion_personal', [
                    'name' => 'Gus Edu',
                    'email' => 'gus@tectron.test',
                    'dni' => $invalido,
                ])
                ->assertSessionHasErrors('dni');
        }

        $this->assertNull($user->fresh()->dni);
    }

    public function test_un_telefono_imposible_se_rechaza(): void
    {
        $user = $this->clienteVinculado();

        $this->actingAs($user)
            ->put('/ajustes/informacion_personal', [
                'name' => 'Gus Edu',
                'email' => 'gus@tectron.test',
                'telefono' => 'abc',
            ])
            ->assertSessionHasErrors('telefono');

        $this->assertNull($user->fresh()->telefono);
    }

    public function test_dejar_el_dni_vacio_lo_borra(): void
    {
        $user = $this->clienteVinculado(['dni' => '70154892']);

        $this->actingAs($user)->put('/ajustes/informacion_personal', [
            'name' => 'Gus Edu',
            'email' => 'gus@tectron.test',
            'dni' => '   ',
        ])->assertSessionHasNoErrors();

        $this->assertNull($user->fresh()->dni);
    }

    public function test_ajustes_muestra_los_datos_que_ya_estan_guardados(): void
    {
        $user = $this->clienteVinculado(['telefono' => '987654321', 'dni' => '70154892']);

        $this->actingAs($user)
            ->get('/ajustes')
            ->assertOk()
            ->assertSee('987654321')
            ->assertSee('70154892');
    }

    public function test_quien_no_esta_logueado_no_puede_editar_el_perfil(): void
    {
        $this->put('/ajustes/informacion_personal', [
            'name' => 'Intruso',
            'email' => 'intruso@tectron.test',
            'telefono' => '999999999',
        ])->assertRedirect('/web/login');
    }
}
