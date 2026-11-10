<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Throwable;

/**
 * Inventario en vivo del panel: entra a cada pantalla GET de /admin y deja
 * constancia de la que no abre. No mide el contenido de la página, solo que el
 * controlador responda, que el permiso exigido esté declarado y que la ruta
 * tenga datos detrás.
 */
class AdminPanelSmokeTest extends TestCase
{
    use RefreshDatabase;

    private const CANDIDATOS = [
        'producto' => ['productos'],
        'imagen' => ['producto_imagens', 'producto_imagenes'],
        'categoria' => ['categorias'],
        'marca' => ['marcas'],
        'oferta' => ['ofertas'],
        'banner' => ['banners'],
        'carrusel' => ['carruseles'],
        'cupon' => ['cupones'],
        'asesor' => ['asesores'],
        'pedido' => ['pedidos', 'ordens'],
        'rol' => ['roles'],
        'usuario' => ['users', 'usuarios'],
        'punto' => ['puntos_recojo'],
        'mensaje' => ['mensajes_contacto'],
        'contacto' => ['contactos'],
        'reserva' => ['reservas_productos'],
        'pais' => ['paises'],
        'departamento' => ['departamentos'],
        'provincia' => ['provincias'],
        'seccion' => ['secciones_home'],
        'ajuste' => ['ajustes'],
        'resena' => ['resenas'],
        'servicio' => ['servicios_tecnicos'],
    ];

    public function test_todas_las_pantallas_del_admin_abren(): void
    {
        $this->tablasCompartidasFaltantes();

        $this->seed();

        $admin = $this->adminConTodo();
        $this->filasMinimas($admin);

        $ok = [];
        $problemas = [];
        $sinDatos = [];

        foreach ($this->pantallasAdmin() as $nombre => $uri) {
            $url = $this->construirUrl($uri, $nombre, $sinDatos);

            if ($url === null) {
                continue;
            }

            try {
                $respuesta = $this->actingAs($admin)->get($url);
                $codigo = $respuesta->getStatusCode();

                if ($codigo >= 500) {
                    $problemas[] = $nombre.' '.$url.' → HTTP '.$codigo.' '.$this->detalle($respuesta);
                } elseif ($codigo === 403) {
                    $problemas[] = $nombre.' '.$url.' → 403, el permiso exigido no está concedido';
                } elseif ($codigo === 404) {
                    $problemas[] = $nombre.' '.$url.' → 404';
                } else {
                    $ok[] = $nombre.' → '.$codigo;
                }
            } catch (Throwable $e) {
                $problemas[] = $nombre.' '.$url.' → '.get_class($e).': '.mb_substr($e->getMessage(), 0, 200);
            }
        }

        fwrite(STDERR, "\n===== PANTALLAS ADMIN OK (".count($ok).") =====\n");
        fwrite(STDERR, implode("\n", $ok)."\n");

        if ($sinDatos !== []) {
            fwrite(STDERR, "\n===== SIN FILAS PARA PROBAR (".count($sinDatos).") =====\n");
            fwrite(STDERR, implode("\n", $sinDatos)."\n");
        }

        $this->assertSame(
            [],
            $problemas,
            "Pantallas del panel que no abren:\n  ".implode("\n  ", $problemas)
        );
    }

    /**
     * Media tienda lee tablas que ninguna migración crea: existen en la base real
     * porque llegaron del importado Tectron, y `default` y `galy` son el mismo
     * `ecommerce`. Se replican con su estructura viva para poder ejercitar los
     * controladores.
     */
    private function tablasCompartidasFaltantes(): void
    {
        $this->crearEnAmbas('producto_imagenes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->string('imagen');
            $table->timestamps();
        });

        $this->crearEnAmbas('pedidos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->decimal('total', 10, 2);
            $table->timestamp('fecha')->nullable();
            $table->string('estado', 50)->nullable();
            $table->boolean('stock_reserved')->default(false);
            $table->string('metodo_pago', 50)->nullable();
            $table->string('metodo_entrega', 20)->nullable();
            $table->string('cliente_email', 100)->nullable();
            $table->string('cliente_dni', 11)->nullable();
            $table->string('cliente_telefono', 20)->nullable();
            $table->string('cliente_nombre')->nullable();
            $table->text('direccion')->nullable();
            $table->string('ciudad', 100)->nullable();
            $table->string('distrito', 100)->nullable();
            $table->text('referencia')->nullable();
            $table->text('maps_url')->nullable();
            $table->string('divisa', 10)->default('PEN');
            $table->string('estado_pago', 50)->default('Pendiente');
            $table->string('estado_orden', 50)->default('En proceso');
            $table->string('transaccion_id')->nullable();
            $table->text('direccion_envio')->nullable();
            $table->string('modalidad_entrega', 30)->default('envio');
            $table->unsignedBigInteger('direccion_id')->nullable();
            $table->text('nota')->nullable();
            $table->string('cupon', 20)->nullable();
            $table->decimal('descuento', 10, 2)->nullable();
            $table->timestamps();
        });

        $this->crearEnAmbas('pedido_productos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pedido_id');
            $table->unsignedBigInteger('producto_id');
            $table->integer('cantidad');
            $table->decimal('precio', 10, 2);
            $table->timestamps();
        });

        $this->crearEnAmbas('pedido_pagos_manuales', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pedido_id');
            $table->unsignedBigInteger('usuario_id');
            $table->string('metodo_pago', 20);
            $table->string('comprobante_path')->nullable();
            $table->string('estado', 30)->default('pendiente');
            $table->timestamp('comprobante_enviado_at')->nullable();
            $table->timestamp('confirmado_at')->nullable();
            $table->unsignedBigInteger('confirmado_por')->nullable();
            $table->string('numero_operacion', 80)->nullable();
            $table->timestamps();
        });

        $this->crearEnAmbas('cupones', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 20);
            $table->integer('descuento_porcentaje');
            $table->date('fecha_expiracion')->nullable();
            $table->boolean('activo')->default(true);
        });

        $this->crearEnAmbas('servicios_tecnicos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('equipo_modelo', 100);
            $table->text('descripcion_falla')->nullable();
            $table->string('estado', 30)->default('Recibido');
            $table->decimal('costo_estimado', 10, 2)->nullable();
            $table->timestamp('fecha_ingreso')->nullable();
            $table->string('imagen_ingreso')->nullable();
            $table->string('imagen_listo')->nullable();
        });

        $this->crearEnAmbas('reservas_productos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->unsignedBigInteger('producto_id');
            $table->integer('cantidad');
            $table->string('estado', 20)->default('activa');
            $table->timestamp('expira_at')->nullable();
            $table->unsignedBigInteger('pedido_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Los escudos de soporte técnico y reservas consultan la conexión GALY, que
     * en pruebas es un sqlite distinto al de los modelos; la tabla se crea en
     * las dos para imitar la base compartida de la tienda real.
     */
    private function crearEnAmbas(string $tabla, \Closure $definicion): void
    {
        foreach ([config('database.default'), 'galy'] as $conexion) {
            if (Schema::connection($conexion)->hasTable($tabla)) {
                continue;
            }

            Schema::connection($conexion)->create($tabla, $definicion);
        }
    }

    /**
     * Sin una fila, las pantallas show/edit no se ejercitan. Se mete un registro
     * por tabla rellenando solo lo que la base exige.
     */
    private function filasMinimas(User $admin): void
    {
        $productoId = DB::table('productos')->value('id') ?? 1;

        $deseadas = [
            'pedidos' => ['usuario_id' => $admin->id, 'total' => 100.00, 'metodo_pago' => 'YAPE'],
            'ordens' => ['usuario_id' => $admin->id, 'total' => 100.00, 'divisa' => 'PEN', 'estado_pago' => 'Pendiente', 'estado_orden' => 'En proceso', 'transaccion_id' => 'PRUEBA-1', 'direccion_envio' => 'Av. Siempre Viva 123'],
            'reservas_productos' => ['usuario_id' => $admin->id, 'producto_id' => $productoId, 'cantidad' => 1, 'expira_at' => now()->addDay()],
            'servicios_tecnicos' => ['usuario_id' => $admin->id, 'equipo_modelo' => 'Laptop de prueba'],
            'mensajes_contacto' => ['nombre' => 'Prueba', 'email' => 'prueba@galy.pe', 'mensaje' => 'Mensaje de prueba'],
            'banners' => ['titulo' => 'Banner de prueba'],
            'carruseles' => ['titulo' => 'Carrusel de prueba'],
            'ofertas' => ['nombre' => 'Oferta de prueba'],
            'asesores' => ['nombre' => 'Asesor de prueba'],
            'puntos_recojo' => ['nombre' => 'Punto de recojo de prueba'],
            'resenas' => ['usuario_id' => $admin->id, 'producto_id' => $productoId, 'comentario' => 'Buena'],
            'cupones' => ['codigo' => 'PRUEBA1', 'descuento_porcentaje' => 10],
        ];

        foreach ($deseadas as $tabla => $preferidas) {
            if (! Schema::hasTable($tabla) || DB::table($tabla)->count() > 0) {
                continue;
            }

            $columnas = array_column(Schema::getColumns($tabla), 'name');
            $fila = array_intersect_key($preferidas, array_flip($columnas));

            foreach (Schema::getColumns($tabla) as $columna) {
                $nombre = $columna['name'];

                if ($nombre === 'id' || isset($fila[$nombre])) {
                    continue;
                }

                if ($columna['default'] !== null || $columna['nullable']) {
                    continue;
                }

                $fila[$nombre] = $this->valorPorTipo($nombre, $columna['type'], $productoId);
            }

            foreach (['created_at', 'updated_at'] as $fecha) {
                if (in_array($fecha, $columnas, true) && ! isset($fila[$fecha])) {
                    $fila[$fecha] = now();
                }
            }

            try {
                DB::table($tabla)->insert($fila);
            } catch (Throwable $e) {
                fwrite(STDERR, "\nNo se pudo crear fila en ".$tabla.': '.$e->getMessage()."\n");
            }
        }

        if (Schema::hasTable('pedido_productos') && DB::table('pedido_productos')->count() === 0) {
            DB::table('pedido_productos')->insert([
                'pedido_id' => DB::table('pedidos')->value('id'),
                'producto_id' => $productoId,
                'cantidad' => 1,
                'precio' => 100.00,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->comprobanteDePrueba((int) DB::table('pedidos')->value('id'), $admin->id);
    }

    /**
     * La pantalla de comprobante baja el archivo desde el disco local: sin ficha
     * y sin respuesta, el 404 sería el comportamiento correcto del controlador y
     * la ruta no quedaría ejercitada.
     */
    private function comprobanteDePrueba(int $pedidoId, int $usuarioId): void
    {
        if ($pedidoId <= 0 || ! Schema::hasTable('pedido_pagos_manuales')) {
            return;
        }

        Storage::disk('local')->put('comprobatorios/prueba.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));

        DB::table('pedido_pagos_manuales')->insert([
            'pedido_id' => $pedidoId,
            'usuario_id' => $usuarioId,
            'metodo_pago' => 'YAPE',
            'comprobante_path' => 'comprobatorios/prueba.png',
            'estado' => 'pendiente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function valorPorTipo(string $nombre, string $tipo, int $productoId)
    {
        $tipo = strtolower($tipo);

        return match (true) {
            str_contains($tipo, 'tinyint') || str_contains($tipo, 'bool') => 1,
            $nombre === 'producto_id' => $productoId,
            str_contains($tipo, 'int') => 1,
            str_contains($tipo, 'decimal') || str_contains($tipo, 'numeric') || str_contains($tipo, 'float') => 10.50,
            str_contains($tipo, 'date') || str_contains($tipo, 'time') => now(),
            default => 'Prueba',
        };
    }

    private function detalle($respuesta): string
    {
        $excepcion = $respuesta->exception;

        if (! $excepcion) {
            return '';
        }

        return get_class($excepcion).': '.mb_substr(str_replace("\n", ' ', $excepcion->getMessage()), 0, 220);
    }

    private function pantallasAdmin(): array
    {
        $pantallas = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $nombre = $route->getName();

            if (! is_string($nombre) || ! str_starts_with($nombre, 'admin.')) {
                continue;
            }

            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $pantallas[$nombre] = $route->uri();
        }

        ksort($pantallas);

        return $pantallas;
    }

    private function construirUrl(string $uri, string $nombre, array &$sinDatos): ?string
    {
        $url = '/'.ltrim($uri, '/');

        foreach ($this->parametros($uri) as $parametro) {
            $tabla = $this->tablaPara($parametro, $url);

            if ($tabla === null) {
                $sinDatos[] = $nombre.' — no sé de qué tabla sacar {'.$parametro.'}';

                return null;
            }

            $id = DB::table($tabla)
                ->when(
                    Schema::hasColumn($tabla, 'deleted_at'),
                    fn ($consulta) => $consulta->whereNull('deleted_at')
                )
                ->value('id');

            if ($id === null) {
                $sinDatos[] = $nombre.' — tabla '.$tabla.' vacía';

                return null;
            }

            $url = preg_replace('/\{'.$parametro.'\??\}/', (string) $id, $url);
        }

        return $url;
    }

    private function parametros(string $uri): array
    {
        preg_match_all('/\{([a-zA-Z_]+)\??\}/', $uri, $encontrados);

        return $encontrados[1] ?? [];
    }

    private function tablaPara(string $parametro, string $url): ?string
    {
        foreach (self::CANDIDATOS as $clave => $tablas) {
            if (! str_contains($parametro, $clave) && ! str_contains($url, $clave)) {
                continue;
            }

            foreach ($tablas as $tabla) {
                if (Schema::hasTable($tabla)) {
                    return $tabla;
                }
            }

            return null;
        }

        return null;
    }

    private function adminConTodo(): User
    {
        $rol = Role::firstOrCreate(['name' => 'SUPER ADMIN', 'guard_name' => 'web']);

        $otorgados = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            foreach ($route->gatherMiddleware() as $middleware) {
                if (! str_starts_with($middleware, 'can:')) {
                    continue;
                }

                foreach (explode(',', mb_substr($middleware, 4)) as $permiso) {
                    if ($permiso === '' || in_array($permiso, $otorgados, true)) {
                        continue;
                    }

                    Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
                    $rol->givePermissionTo($permiso);
                    $otorgados[] = $permiso;
                }
            }
        }

        $admin = User::factory()->create([
            'galy_es_admin' => true,
            'galy_user_id' => (int) DB::table('users')->max('id') + 1,
        ]);

        $admin->assignRole($rol);

        return $admin;
    }
}
