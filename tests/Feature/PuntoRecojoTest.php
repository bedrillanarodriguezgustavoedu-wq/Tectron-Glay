<?php

namespace Tests\Feature;

use App\Models\Orden;
use App\Models\Producto;
use App\Models\PuntoRecojo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\CreatesOrderPaymentTables;
use Tests\TestCase;

/**
 * La tienda puede publicar varios puntos de recojo y el cliente elige uno. Sin
 * puntos publicados el carrito sigue mostrando la dirección general de Ajustes,
 * así que ninguna de las dos versiones puede romperse con la otra.
 */
class PuntoRecojoTest extends TestCase
{
    use CreatesOrderPaymentTables;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearTablasDePagos();
    }

    public function test_el_carrito_lista_cada_punto_de_recojo_con_sus_propios_datos(): void
    {
        $usuario = $this->crearClienteConCarrito();

        PuntoRecojo::create([
            'nombre' => 'GALY Miraflores',
            'direccion' => 'Calle Las Palmeras 245',
            'horario' => 'Lun a Sáb, 10:00 a 19:00',
            'telefono' => '911222333',
            'orden' => 1,
        ]);
        PuntoRecojo::create([
            'nombre' => 'GALY Surquillo',
            'direccion' => 'Av. Surquillo 1580',
            'horario' => 'Lun a Vie, 9:00 a 18:00',
            'telefono' => '944555666',
            'orden' => 2,
        ]);
        PuntoRecojo::create([
            'nombre' => 'GALY cerrado',
            'direccion' => 'Jr. Fulano 99',
            'activo' => false,
        ]);

        $html = $this->actingAs($usuario)->get(route('web.carrito.index'))->getContent();

        $this->assertContainsString($html, 'Elige dónde recoger');
        $this->assertContainsString($html, 'GALY Miraflores');
        $this->assertContainsString($html, 'Calle Las Palmeras 245');
        $this->assertContainsString($html, 'Lun a Sáb, 10:00 a 19:00');
        $this->assertContainsString($html, 'GALY Surquillo');
        $this->assertContainsString($html, 'Av. Surquillo 1580');
        $this->assertNotContainsString($html, 'GALY cerrado', 'Un punto oculto no puede ofrecerse en el carrito.');

        // La versión de un solo punto ya no aplica cuando hay lista: si
        // quedara visible, el cliente vería dos destinos distintos.
        $this->assertNotContainsString($html, '<div class="punto-recojo-card p-3">');
        $this->assertSame(2, substr_count($html, 'name="punto_recojo_id" id="punto-recojo-'));
    }

    public function test_sin_puntos_publicados_el_carrito_usa_la_direccion_general(): void
    {
        $usuario = $this->crearClienteConCarrito();

        $html = $this->actingAs($usuario)->get(route('web.carrito.index'))->getContent();

        $this->assertContainsString($html, 'Av. Siempre Viva 123');
        // El JS siempre menciona el nombre del campo: hay que buscar la
        // etiqueta del <input>, no la cadena suelta.
        $this->assertNotContainsString($html, 'name="punto_recojo_id" id="punto-recojo-');
        $this->assertContainsString($html, '<div class="punto-recojo-card p-3">');
    }

    public function test_el_recojo_exige_elegir_un_punto_cuando_la_tienda_tiene_varios(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $punto = $this->crearPuntos();

        $this->actingAs($usuario)
            ->from(route('web.carrito.index'))
            ->post(route('web.pedidos.pago_manual'), $this->datosCheckout(null))
            ->assertSessionHasErrors('punto_recojo_id');

        $this->assertSame(0, DB::table('pedidos')->count());

        // Un punto oculto no es un destino válido aunque exista en la base.
        $this->actingAs($usuario)
            ->post(route('web.pedidos.pago_manual'), $this->datosCheckout($punto->oculto->id))
            ->assertSessionHasErrors('punto_recojo_id');

        $this->assertSame(0, DB::table('pedidos')->count());
    }

    public function test_el_pedido_guarda_el_punto_elegido_en_su_direccion_de_envio(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $punto = $this->crearPuntos();

        $this->actingAs($usuario)
            ->post(route('web.pedidos.pago_manual'), $this->datosCheckout($punto->miraflores->id))
            ->assertRedirect();

        // `direccion_envio` y la modalidad son accesores que la app Android
        // comparte con las columnas heredadas de `pedidos`: hay que leerlos por
        // el modelo, no por la tabla cruda.
        $pedido = Orden::sole();

        $this->assertSame('Recojo en GALY Miraflores – Calle Las Palmeras 245', $pedido->direccion_envio);
        $this->assertSame('recojo', $pedido->modalidad_entrega);
        $this->assertNull($pedido->direccion_id, 'El recojo no gasta la dirección registrada del cliente.');
    }

    public function test_un_punto_de_recojo_no_se_le_exige_a_quien_pide_envio_a_domicilio(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $puntos = $this->crearPuntos();
        $direccion = $this->crearDireccion($usuario, 'Huamanga');

        // El punto de recojo pertenece a la modalidad de recojo: exigirle a un
        // cliente que reparte en su casa lo dejaría sin poder pagar.
        $this->actingAs($usuario)
            ->post(route('web.pedidos.pago_manual'), $this->datosDomicilio($direccion->id))
            ->assertSessionHasNoErrors();

        $pedido = Orden::sole();
        $this->assertSame('envio', $pedido->modalidad_entrega);
        $this->assertStringNotContainsString($puntos->miraflores->nombre, $pedido->direccion_envio);
    }

    public function test_el_envio_a_provincias_ofrece_la_agencia_de_la_ciudad_del_cliente(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $direccion = $this->crearDireccion($usuario, 'Huamanga');
        $agencias = $this->crearAgencias();

        $html = $this->actingAs($usuario)->get(route('web.carrito.index'))->getContent();

        $this->assertContainsString($html, 'id="agencias-provincia-card"');
        $this->assertContainsString($html, 'Elige dónde recoger en Huamanga');
        $this->assertContainsString($html, 'name="agencia_provincia_id" id="agencia-'.$agencias->huamanga->id.'"');
        $this->assertContainsString($html, 'Jr. Independencia 450');

        // Cada grupo lleva su provincia para que el JS muestre solo el de la
        // ciudad elegida: la otra agencia existe en el HTML pero nunca visible.
        $this->assertContainsString($html, 'data-provincia-id="'.$direccion->provincia_id.'"');
        $this->assertContainsString($html, 'data-provincia-id="'.$agencias->chachapoyas->provincia_id.'"');
        // El recojo en tienda no se mezcla: una agencia no es un punto de la tienda.
        $this->assertNotContainsString($html, 'name="punto_recojo_id" id="punto-recojo-');
    }

    public function test_sin_agencias_publicadas_el_envio_a_provincias_se_comporta_como_antes(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $direccion = $this->crearDireccion($usuario, 'Huamanga');

        $html = $this->actingAs($usuario)->get(route('web.carrito.index'))->getContent();
        $this->assertNotContainsString($html, 'id="agencias-provincia-card"');

        $this->actingAs($usuario)
            ->post(route('web.pedidos.pago_manual'), $this->datosProvincia($direccion->id))
            ->assertSessionHasNoErrors();

        $pedido = Orden::sole();
        $this->assertSame('provincia', $pedido->modalidad_entrega);
        $this->assertStringContainsString('jr cristo rey 123', $pedido->direccion_envio);
    }

    public function test_el_pedido_a_provincias_exige_la_agencia_de_su_propia_ciudad(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $direccion = $this->crearDireccion($usuario, 'Huamanga');
        $agencias = $this->crearAgencias();

        $this->actingAs($usuario)
            ->from(route('web.carrito.index'))
            ->post(route('web.pedidos.pago_manual'), $this->datosProvincia($direccion->id))
            ->assertSessionHasErrors('agencia_provincia_id');

        // Una agencia de otra provincia no sirve: el pedido nunca llegaría ahí.
        $this->actingAs($usuario)
            ->post(route('web.pedidos.pago_manual'), $this->datosProvincia($direccion->id, $agencias->chachapoyas->id))
            ->assertSessionHasErrors('agencia_provincia_id');

        $this->assertSame(0, DB::table('pedidos')->count());

        $this->actingAs($usuario)
            ->post(route('web.pedidos.pago_manual'), $this->datosProvincia($direccion->id, $agencias->huamanga->id))
            ->assertSessionHasNoErrors();

        $pedido = Orden::sole();
        $this->assertSame(
            'Agencia GALY Ayacucho (Huamanga, Ayacucho) – Jr. Independencia 450',
            $pedido->direccion_envio
        );
        $this->assertSame($direccion->id, $pedido->direccion_id, 'La calle del cliente sigue ligada al pedido.');
    }

    public function test_el_panel_admin_registra_una_agencia_con_su_provincia(): void
    {
        $this->adminConPermisos([
            'Listado de Punto de Recojo', 'Crear Punto de Recojo', 'Guardar Punto de Recojo',
        ]);

        $provincia = \App\Models\Provincia::where('nombre', 'Huamanga')->firstOrFail();

        $this->get('/admin/puntos-recojo/create')
            ->assertSuccessful()
            ->assertSee('Agencia en una provincia');

        $this->from('/admin/puntos-recojo/create')
            ->post('/admin/puntos-recojo/create', [
                'nombre' => 'GALY Ayacucho',
                'direccion' => 'Jr. Independencia 450',
                'provincia_id' => $provincia->id,
                'orden' => 1,
                'activo' => '1',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('puntos_recojo', [
            'nombre' => 'GALY Ayacucho',
            'provincia_id' => $provincia->id,
        ]);

        // El listado tiene que decir para qué sirve cada fila: sin eso, un punto
        // de tienda y una agencia de Ayacucho se ven idénticos.
        $this->get('/admin/puntos-recojo')
            ->assertSuccessful()
            ->assertSee('Agencia en Huamanga');

        // Una provincia que ya no está en las zonas de envío no puede guardarse.
        $this->post('/admin/puntos-recojo/create', [
            'nombre' => 'Agencia fantasma',
            'direccion' => 'Calle 1',
            'provincia_id' => 999999,
            'activo' => '1',
        ])->assertSessionHasErrors('provincia_id');
    }

    public function test_el_formulario_agrupa_las_provincias_por_departamento(): void
    {
        $this->adminConPermisos(['Listado de Punto de Recojo', 'Editar Punto de Recojo']);

        $huamanga = \App\Models\Provincia::where('nombre', 'Huamanga')->firstOrFail();
        $agencia = PuntoRecojo::create([
            'nombre' => 'GALY Ayacucho',
            'direccion' => 'Jr. Independencia 450',
            'provincia_id' => $huamanga->id,
        ]);

        $html = $this->get('/admin/puntos-recojo/'.$agencia->id.'/edit')
            ->assertSuccessful()
            ->getContent();

        // 196 provincias sueltas no se encuentran: cada grupo tiene que decir a qué
        // departamento pertenece y la provincia elegida tiene que vivir dentro.
        $inicioGrupo = strpos($html, '<optgroup label="Ayacucho">');
        $this->assertTrue($inicioGrupo !== false, 'Falta el grupo de Ayacucho.');

        $finGrupo = strpos($html, '</optgroup>', $inicioGrupo);
        $grupo = substr($html, $inicioGrupo, $finGrupo - $inicioGrupo);
        $this->assertTrue(str_contains($grupo, 'Huamanga'), 'Huamanga no quedó dentro de su departamento.');
        $this->assertTrue(str_contains($grupo, 'Lucanas'), 'El grupo está incompleto.');
        $this->assertTrue(
            (bool) preg_match('/value="'.$huamanga->id.'"\s+selected/', $grupo),
            'La provincia guardada no quedó marcada dentro de su grupo.'
        );
    }

    public function test_el_listado_se_puede_filtrar_por_tipo_y_por_departamento(): void
    {
        $this->adminConPermisos(['Listado de Punto de Recojo']);
        $this->crearPuntos();
        $this->crearAgencias();

        $agencias = $this->get('/admin/puntos-recojo?tipo=agencia')->getContent();
        $this->assertContainsString($agencias, 'GALY Ayacucho');
        $this->assertNotContainsString($agencias, 'GALY Miraflores', 'Un punto de tienda no es una agencia.');

        $tienda = $this->get('/admin/puntos-recojo?tipo=tienda')->getContent();
        $this->assertContainsString($tienda, 'GALY Miraflores');
        $this->assertNotContainsString($tienda, 'GALY Ayacucho', 'Una agencia no se lista como punto de tienda.');

        $ayacucho = \App\Models\Departamento::where('nombre', 'Ayacucho')->firstOrFail();
        $porDepartamento = $this->get('/admin/puntos-recojo?departamento_id='.$ayacucho->id)->getContent();
        $this->assertContainsString($porDepartamento, 'GALY Ayacucho');
        $this->assertNotContainsString($porDepartamento, 'GALY Amazonas', 'Esa agencia es de otro departamento.');

        // El filtro elegido tiene que volver marcado, si no, al limpiar por error
        // el panel vuelve a la lista completa sin decir por qué.
        $this->assertTrue(
            (bool) preg_match('/value="'.$ayacucho->id.'"\s+selected/', $porDepartamento),
            'El departamento filtrado no quedó marcado.'
        );
    }

    public function test_el_listado_deja_al_final_lo_que_no_se_esta_ofreciendo(): void
    {
        $this->adminConPermisos(['Listado de Punto de Recojo']);
        $this->crearPuntos();
        $this->crearAgencias();

        $html = $this->get('/admin/puntos-recojo')->getContent();

        // Con decenas de locales, un oculto mezclado en la primera página se lee
        // como si estuviera activo: lo visible y la tienda propia van primero.
        $posiciones = collect([
            'GALY Miraflores',
            'GALY Surquillo',
            'GALY Ayacucho',
            'GALY Amazonas',
            'GALY cerrado',
        ])->mapWithKeys(fn ($nombre) => [$nombre => strpos($html, $nombre)]);

        $this->assertTrue($posiciones->take(4)->every(fn ($p) => $p !== false));
        $this->assertTrue($posiciones['GALY Miraflores'] < $posiciones['GALY Ayacucho']);
        $this->assertTrue($posiciones['GALY Amazonas'] < $posiciones['GALY cerrado']);
    }

    public function test_el_carrito_lleva_donde_pintar_el_lugar_elegido_y_su_aviso(): void
    {
        $usuario = $this->crearClienteConCarrito();
        $this->crearDireccion($usuario, 'Huamanga');
        // Los dos escenarios en la misma página: el resumen tiene que decir el
        // punto de la tienda o la agencia según la modalidad elegida.
        $this->crearPuntos();
        $this->crearAgencias();

        $html = $this->actingAs($usuario)->get(route('web.carrito.index'))->getContent();

        // El resumen confirma el punto elegido: sin esta línea el cliente decide
        // dónde recoger y la pantalla nunca se lo devuelve.
        $this->assertContainsString($html, 'id="delivery-point-line"');
        // Y cuando su ciudad no tiene agencia, lo dice en vez de esconder el bloque.
        $this->assertContainsString($html, 'id="agencia-provincia-nota"');
        $this->assertContainsString($html, 'Aún no tenemos agencia en la ciudad de esa dirección');
    }

    /**
     * Una agencia por provincia: la del cliente y la de otra ciudad, para probar
     * que el filtro es por destino y no "cualquier agencia".
     */
    private function crearAgencias(): object    {
        $huamanga = PuntoRecojo::create([
            'nombre' => 'GALY Ayacucho',
            'direccion' => 'Jr. Independencia 450',
            'horario' => 'Lun a Sáb, 9:00 a 18:00',
            'telefono' => '955111222',
            'provincia_id' => \App\Models\Provincia::where('nombre', 'Huamanga')->value('id'),
        ]);

        $chachapoyas = PuntoRecojo::create([
            'nombre' => 'GALY Amazonas',
            'direccion' => 'Jr. Sáenz 123',
            'provincia_id' => \App\Models\Provincia::where('nombre', 'Chachapoyas')->value('id'),
        ]);

        $oculta = PuntoRecojo::create([
            'nombre' => 'GALY Cusco cerrado',
            'direccion' => 'Calle Imaginaria 1',
            'provincia_id' => \App\Models\Provincia::where('nombre', 'Cusco')->value('id'),
            'activo' => false,
        ]);

        return (object) compact('huamanga', 'chachapoyas', 'oculta');
    }

    private function crearDireccion(User $usuario, string $provinciaNombre): \App\Models\Direccion
    {
        $provincia = \App\Models\Provincia::where('nombre', $provinciaNombre)->firstOrFail();
        $departamento = \App\Models\Departamento::findOrFail($provincia->departamento_id);

        return \App\Models\Direccion::create([
            'usuario_id' => $usuario->id,
            'alias' => 'Casa',
            'pais_id' => $departamento->pais_id,
            'departamento_id' => $departamento->id,
            'provincia_id' => $provincia->id,
            'direccion_exacta' => 'jr cristo rey 123',
            'telefono' => '987654321',
            'es_predeterminada' => true,
        ]);
    }

    private function datosProvincia(int $direccionId, ?int $agenciaId = null): array
    {
        // `+` daría prioridad a los datos de recojo del helpers base: el array
        // de la derecha tiene que ganar o la modalidad nunca cambiaría.
        $datos = array_merge($this->datosCheckout(null), [
            'direccion_id' => $direccionId,
            'modalidad_entrega' => 'provincia',
            'shipping_option' => 'provincia',
        ]);

        if ($agenciaId !== null) {
            $datos['agencia_provincia_id'] = $agenciaId;
        }

        return $datos;
    }

    private function datosDomicilio(int $direccionId): array
    {
        return array_merge($this->datosCheckout(null), [
            'direccion_id' => $direccionId,
            'modalidad_entrega' => 'envio',
            'shipping_option' => 'standard',
        ]);
    }

    /**
     * Dos puntos activos y uno oculto. Se crean siempre igual para que las
     * pruebas de render y las de validación hablen del mismo escenario.
     */
    private function crearPuntos(): object
    {
        $miraflores = PuntoRecojo::create([
            'nombre' => 'GALY Miraflores',
            'direccion' => 'Calle Las Palmeras 245',
            'horario' => 'Lun a Sáb, 10:00 a 19:00',
            'telefono' => '911222333',
            'orden' => 1,
        ]);

        $surquillo = PuntoRecojo::create([
            'nombre' => 'GALY Surquillo',
            'direccion' => 'Av. Surquillo 1580',
            'orden' => 2,
        ]);

        $oculto = PuntoRecojo::create([
            'nombre' => 'GALY cerrado',
            'direccion' => 'Jr. Fulano 99',
            'activo' => false,
        ]);

        return (object) compact('miraflores', 'surquillo', 'oculto');
    }

    private function datosCheckout(?int $puntoId): array
    {
        $datos = [
            'name' => 'Cliente GALY',
            'dni' => '12345678',
            'telefono' => '987654321',
            'privacy_consent' => '1',
            'metodo_pago' => 'yape',
            'modalidad_entrega' => 'recojo',
        ];

        if ($puntoId !== null) {
            $datos['punto_recojo_id'] = $puntoId;
        }

        return $datos;
    }

    /**
     * Cliente con un producto en el carrito y el Yape del canal web configurado,
     * con la dirección de recojo general de relleno que usa la tienda hoy.
     */
    private function crearClienteConCarrito(): User
    {
        $usuario = User::factory()->create([
            'galy_user_id' => 42,
            'google_id' => 'google-42',
            'email_verified_at' => now(),
        ]);

        $producto = Producto::factory()->create(['stock' => 5]);
        $producto->forceFill([
            'precio_venta' => 550,
            'precio_oferta' => null,
            'en_oferta' => false,
        ])->save();

        DB::connection('galy')->table('productos')->insert([
            'id' => $producto->id,
            'categoria_id' => $producto->categoria_id,
            'stock' => 5,
        ]);

        DB::table('ajustes')->insert([
            'nombre' => 'GALY',
            'descripcion' => 'Tienda',
            'sucursal' => 'Principal',
            'direccion' => 'Av. Siempre Viva 123',
            'telefono' => '0998765432',
            'logo' => 'logo.png',
            'imagen_login' => 'login.png',
            'email' => 'tienda@example.com',
            'divisa' => 'PEN',
            'web_yape_numero' => '901095562',
            'web_yape_titular' => 'Gustavo Edu Bedrillana Rodríguez',
            'web_yape_qr' => 'pagos/qr/web/yape.png',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('carrito')->insert([
            'usuario_id' => $usuario->galy_user_id,
            'producto_id' => $producto->id,
            'cantidad' => 1,
        ]);

        return $usuario;
    }

    /**
     * Rol con los permisos del panel web. El super admin real entra por
     * `Gate::before`, pero así se prueba también que las rutas están protegidas
     * por los permisos que RoleSeeder declara.
     */
    private function adminConPermisos(array $permisos): User
    {
        $role = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'SUPER ADMIN', 'guard_name' => 'web',
        ]);

        foreach ($permisos as $nombre) {
            $role->givePermissionTo(\Spatie\Permission\Models\Permission::firstOrCreate([
                'name' => $nombre, 'guard_name' => 'web',
            ]));
        }

        $admin = User::factory()->create();
        $admin->assignRole($role);
        $this->actingAs($admin);

        return $admin;
    }

    /**
     * `assertSee` sobre esta página agota los 128M de phpunit cuando el texto no
     * está: el formateador del fallo vuelca 150 KB de HTML.
     */
    private function assertContainsString(string $html, string $aguja): void
    {
        $this->assertTrue(str_contains($html, $aguja), 'Falta en el HTML: '.$aguja);
    }

    private function assertNotContainsString(string $html, string $aguja, string $motivo = ''): void
    {
        $this->assertFalse(str_contains($html, $aguja), ($motivo !== '' ? $motivo : 'No debería estar en el HTML: ').$aguja);
    }
}
