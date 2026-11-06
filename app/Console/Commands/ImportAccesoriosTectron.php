<?php

namespace App\Console\Commands;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\ProductoImagen;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportAccesoriosTectron extends Command
{
    protected $signature = 'galy:import-accesorios
        {--apply : Escribe en la base de datos; sin esta opción solo muestra lo que haría}
        {--solo-imagenes : Solo vincula las fotos que ya estén descargadas}';

    protected $description = 'Carga el inventario físico de accesorios de Tectron en el catálogo de la web, con descripción larga y detalles técnicos';

    public function handle(): int
    {
        if (app()->environment('testing')) {
            $this->components->error('La carga no se permite con APP_ENV=testing.');

            return self::FAILURE;
        }

        $filas = require base_path('database/data/accesorios-tectron.php');
        $precios = require base_path('database/data/accesorios-tectron-precios.php');
        $galy = DB::connection('galy');

        $categorias = Categoria::all()->keyBy(fn ($c) => $this->normal($c->nombre));
        $existentes = $galy->table('productos')->whereNull('deleted_at')
            ->get(['id', 'codigo', 'nombre'])->keyBy(fn ($p) => $this->normal($p->nombre));

        $creados = $actualizados = $sinCategoria = $conImagen = 0;
        $reclamados = [];

        foreach ($filas as $fila) {
            $codigo = 'ACC-'.str_pad((string) $fila['f'], 3, '0', '0');
            $imagen = $this->imagenDisponible($fila['f']);
            $precio = $precios[$fila['f']] ?? ['pc' => 0, 'pv' => 0];

            if ($this->option('solo-imagenes')) {
                $destino = ($fila['y'] ?? null)
                    ? $galy->table('productos')->where('codigo', $fila['y'])->whereNull('deleted_at')->first(['id'])
                    : null;
                $destino = $destino
                    ?? $galy->table('productos')->where('codigo', $codigo)->whereNull('deleted_at')->first(['id'])
                    ?? ($existentes[$this->normal($fila['n'])] ?? null);

                if ($destino && $imagen && $this->vincularImagen($destino->id, $imagen)) {
                    $conImagen++;
                }

                continue;
            }

            $categoria = $categorias[$this->normal($fila['cat'])] ?? null;
            if (! $categoria) {
                $this->warn("Sin categoría: {$fila['n']} ({$fila['cat']})");
                $sinCategoria++;

                continue;
            }

            $textos = $this->textos($fila);
            $marcada = $this->marca($fila['marca']);
            $YaExiste = ($fila['y'] ?? null)
                ? $galy->table('productos')->where('codigo', $fila['y'])->whereNull('deleted_at')->first(['id'])
                : null;
            if ($YaExiste && isset($reclamados[$YaExiste->id])) {
                $YaExiste = null;
            }
            if (! $YaExiste) {
                // El código ACC-### es estable entre ejecuciones; búscalo antes
                // del nombre para no quedarse con el producto de otra fila.
                $YaExiste = $galy->table('productos')->where('codigo', $codigo)->whereNull('deleted_at')->first(['id']);
            }
            if (! $YaExiste) {
                // Dos filas pueden llamarse igual y diferenciarse solo por el
                // modelo (Mouse Genius DX-101 / DX-110): no reutilices la misma.
                $porNombre = $existentes[$this->normal($fila['n'])] ?? null;
                $YaExiste = $porNombre && ! isset($reclamados[$porNombre->id]) ? $porNombre : null;
            }
            if ($YaExiste) {
                $reclamados[$YaExiste->id] = true;
            }

            if ($YaExiste) {
                if ($this->option('apply')) {
                    $producto = Producto::find($YaExiste->id);
                    $producto->stock = $fila['c'];
                    $producto->categoria_id = $producto->categoria_id ?: $categoria->id;
                    $producto->marca_id = $producto->marca_id ?: $marcada?->id;
                    $producto->codigo = $producto->codigo ?: $codigo;
                    $producto->categoria_nombre = $categoria->nombre;
                    $producto->nombre = $this->ajustarNombre($producto->nombre, $fila);
                    if ((float) $producto->precio_compra <= 0) {
                        $producto->precio_compra = $precio['pc'];
                    }
                    if ((float) $producto->precio_venta <= 0) {
                        $producto->precio_venta = $precio['pv'];
                    }
                    if ((float) ($producto->attributes['precio_oferta'] ?? 0) <= 0) {
                        $producto->precio_oferta = $precio['pv'];
                    }
                    $producto->disponible = (int) $fila['c'] > 0;
                    $vacio = static fn ($valor) => trim((string) $valor) === '';
                    $producto->descripcion_corta = $vacio($producto->descripcion_corta) ? $textos['corta'] : $producto->descripcion_corta;
                    $producto->descripcion_larga = $vacio($producto->descripcion_larga) ? $textos['larga'] : $producto->descripcion_larga;
                    $producto->detalles_tecnicos = $vacio($producto->detalles_tecnicos) ? $textos['detalles'] : $producto->detalles_tecnicos;
                    $producto->save();

                    if ($imagen && $this->vincularImagen($producto->id, $imagen)) {
                        $conImagen++;
                    }
                }

                $actualizados++;

                continue;
            }

            if ($this->option('apply')) {
                $producto = new Producto;
                $producto->fill([
                    'categoria_id' => $categoria->id,
                    'marca_id' => $marcada?->id,
                    'nombre' => $this->nombreConModelo($fila['n'], $fila['m'] ?? null),
                    'codigo' => $codigo,
                    'descripcion_corta' => $textos['corta'],
                    'descripcion_larga' => $textos['larga'],
                    'detalles_tecnicos' => $textos['detalles'],
                    'precio_compra' => $precio['pc'],
                    'precio_venta' => $precio['pv'],
                    'precio_oferta' => $precio['pv'],
                    'stock' => $fila['c'],
                ]);
                $producto->marca = $fila['marca'] ?: '';
                $producto->categoria_nombre = $categoria->nombre;
                $producto->disponible = (int) $fila['c'] > 0;
                $producto->save();

                if ($imagen && $this->vincularImagen($producto->id, $imagen)) {
                    $conImagen++;
                }
            }

            $creados++;
        }

        if ($this->option('solo-imagenes')) {
            $this->components->info('Fotos vinculadas: '.$conImagen.'.');

            return self::SUCCESS;
        }

        $prefijo = $this->option('apply') ? '' : '(simulación) ';
        $this->components->info($prefijo.$creados.' por crear, '.$actualizados.' ya existentes que se actualizan, '.$sinCategoria.' sin categoría y '.$conImagen.' con foto.');

        if ($this->option('apply')) {
            $this->line('Los precios salen de database/data/accesorios-tectron-precios.php (referenciales).');
            $this->line('Para cambiarlos: Admin → Productos → Editar, o edita ese archivo y vuelve a ejecutar.');
        } else {
            $this->line('Ejecuta con --apply para escribir en la base.');
        }

        return self::SUCCESS;
    }

    private function normal(?string $valor): string
    {
        $valor = Str::of((string) $valor)->lower()->pipe(fn ($v) => iconv('UTF-8', 'ASCII//TRANSLIT', $v));

        return preg_replace('/\s+/', ' ', trim((string) $valor));
    }

    /**
     * La lista repite el nombre para varias referencias (Mouse Genius DX-101 y
     * DX-110). El modelo es lo único que las distingue en la tienda, así que se
     * agrega al nombre cuando todavía no aparece.
     */
    private function nombreConModelo(?string $nombre, ?string $modelo): string
    {
        $nombre = trim((string) $nombre);
        $modelo = trim((string) $modelo);

        if ($modelo === '' || str_contains($this->normal($nombre), $this->normal($modelo))) {
            return $nombre;
        }

        return trim($nombre.' '.$modelo);
    }

    /**
     * Corrige nombres que ya salieron de esta lista y que, en una ejecución
     * anterior, acumularon el modelo de otra fila con el mismo nombre base.
     * Los nombres escritos a mano (SKIN, FUNDA DE 13...) no se tocan.
     *
     * @param  array<string, mixed>  $fila
     */
    private function ajustarNombre(?string $actual, array $fila): string
    {
        $actual = trim((string) $actual);
        $base = $this->normal($fila['n']);
        $normalActual = $this->normal($actual);

        if ($normalActual !== $base && str_starts_with($normalActual, $base.' ')) {
            return $this->nombreConModelo($fila['n'], $fila['m'] ?? null);
        }

        return $this->nombreConModelo($actual, $fila['m'] ?? null);
    }

    private function marca(?string $nombre): ?Marca
    {
        if ($nombre === null || trim($nombre) === '') {
            return null;
        }

        $clave = $this->normal($nombre);
        $existente = Marca::all()->first(fn (Marca $m) => $this->normal($m->nombre) === $clave);
        if ($existente) {
            return $existente;
        }

        if (! $this->option('apply')) {
            return null;
        }

        return Marca::create(['nombre' => $nombre, 'slug' => Str::slug($nombre), 'activo' => true]);
    }

    private function imagenDisponible(int $fila): ?string
    {
        $archivo = 'productos/accesorios/acc-'.$fila.'.jpg';

        return is_file(public_path('storage/'.$archivo)) ? $archivo : null;
    }

    private function vincularImagen(int $productoId, string $archivo): bool
    {
        $yaVinculada = ProductoImagen::where('producto_id', $productoId)
            ->where('imagen', $archivo)->exists();
        if ($yaVinculada) {
            return false;
        }

        ProductoImagen::create(['producto_id' => $productoId, 'imagen' => $archivo]);

        return true;
    }

    /**
     * @return array{corta: string, larga: string, detalles: string}
     */
    private function textos(array $fila): array
    {
        $marca = $fila['marca'] ?: 'Tectron';
        $modelo = $fila['m'] ? ' modelo <strong>'.$fila['m'].'</strong>' : '';
        $datos = $fila['datos'];
        $extra = $datos ? collect($datos)->map(fn ($v, $k) => ucfirst($k).': '.$v)->implode(', ') : null;

        $plantilla = $this->plantilla($fila['fam']);

        $corta = $fila['n'].' ('.$fila['cat'].') – '.$plantilla['corta'].($extra ? ' '.$extra.'.' : '');
        $corta = Str::limit(trim($corta), 250);

        $bullets = collect($plantilla['bullets'])
            ->merge($extra ? ['Detalle: '.$extra.'.'] : [])
            ->map(fn ($b) => '<li>'.$b.'</li>')->implode("\n");

        $larga = '<p>'.$marca.' '.$plantilla['sujeto'].''.$modelo.' '.$plantilla['intro'].'</p>'
            ."<ul>\n".$bullets."\n</ul>"
            .'<p>Es stock físico de la tienda Tectron, contado en almacén. La cantidad publicada es la unidad que '
            .'tenemos a la mano en este momento; si necesitas más unidades de las que muestra la ficha, escríbenos '
            .'y verificamos el ingreso siguiente antes de que pagues.</p>';

        $filas = [
            'Producto' => $fila['n'],
            'Categoría' => $fila['cat'],
            'Marca' => $fila['marca'] ?: 'Genérico',
        ];
        if ($fila['m']) {
            $filas['Modelo'] = $fila['m'];
        }
        foreach ($datos as $k => $v) {
            $filas[ucfirst($k)] = $v;
        }
        $filas['Referencia interna'] = 'ACC-'.str_pad((string) $fila['f'], 3, '0', '0');
        $filas['Unidades en almacén'] = (string) $fila['c'];

        $detalles = '<table>'.collect($filas)->map(fn ($v, $k) => '<tr><td>'.$k.'</td><td>'.$v.'</td></tr>')->implode('').'</table>';

        return ['corta' => $corta, 'larga' => $larga, 'detalles' => $detalles];
    }

    /**
     * @return array{corta: string, sujeto: string, intro: string, bullets: array<int, string>}
     */
    private function plantilla(string $familia): array
    {
        return match ($familia) {
            'protector_teclado' => [
                'corta' => 'Lámina flexible que cubre el teclado completo sin dejar marcas.',
                'sujeto' => 'protector de teclado',
                'intro' => 'Se apoya sobre las teclas y bloquea el paso de polvo, migajas y salpicaduras, que son la causa más común de teclados que dejan de responder. Se levanta y se lava con agua y jabón neutro.',
                'bullets' => ['No interfiere con el tacto de las teclas ni con la retroiluminación.', 'Se recorta según el tamaño indicado del chasis.', 'Reversible: puedes retirarlo para limpiar el equipo.'],
            ],
            'protector_pantalla' => [
                'corta' => 'Lámina transparente que cuida la pantalla contra rayones.',
                'sujeto' => 'protector de pantalla',
                'intro' => 'Cubre el panel con una película delgada que absorbe el roce de fundas, mochilas y uñas, y mantiene la nitidez original del display. Se adhiere por estática, sin pegamento.',
                'bullets' => ['Superficie mate o brillante según el lote; no altera los colores.', 'Incluye guía de alineación para colocarlo sin burbujas.', 'Se puede retirar sin dejar residuo.'],
            ],
            'skin' => [
                'corta' => 'Vinil adhesivo decorativo para la tapa de la laptop.',
                'sujeto' => 'skin',
                'intro' => 'Vinil delgado que se pega en la tapa del equipo para cambiarle el acabado y protegerlo de rayones. Se corta según el modelo y no tapa ventilaciones ni logos.',
                'bullets' => ['Adhesivo de baja intensidad: se despega sin marcar.', 'Acabado disponible según el lote ingresado.', 'Limpia la superficie antes de colocarlo para que no queden burbujas.'],
            ],
            'pad_mouse' => [
                'corta' => 'Base para el mouse que mejora el deslizamiento.',
                'sujeto' => 'pad de mouse',
                'intro' => 'Da una superficie pareja al cursor, que es donde más se nota la diferencia entre una mesa y un pad. La base de goma no se resbala sobre el escritorio.',
                'bullets' => ['Borde cosido que evita que se deshilache.', 'Se limpia con paño húmedo y se seca rápido.', 'Grosor pensado para uso continuo de oficina.'],
            ],
            'funda' => [
                'corta' => 'Funda de transporte acolchada para laptop.',
                'sujeto' => 'funda para laptop',
                'intro' => 'Acolchado de espuma entre la tela y el equipo, que es lo que absorbe el golpe cuando la laptop viaja en otra mochila o se cae de la mano. El cierre cubre el contorno completo.',
                'bullets' => ['Bolsillo frontal para cargador y accesorios.', 'Tela exterior resistente al roce.', 'Medida exacta del tamaño indicado en la ficha.'],
            ],
            'mochila' => [
                'corta' => 'Mochila con compartimento acolchado para laptop.',
                'sujeto' => 'mochila para laptop',
                'intro' => 'Compartimento interno acolchado que aísla el equipo del resto del equipaje, con espalda reforzada para uso diario en calle y transporte público.',
                'bullets' => ['Tirantes ajustables con relleno.', 'Bolsillos separados para cargador, cables y documentos.', 'Cierre con tirador reforzado.'],
            ],
            'kit_limpieza' => [
                'corta' => 'Set de herramientas para limpieza interna y externa.',
                'sujeto' => 'kit de limpieza',
                'intro' => 'Reúne lo que se usa en un mantenimiento básico: brocha para el polvo de ventilaciones, paño que no suelta pelusa y aplicador para la pantalla. Es el kit con el que se hace la limpieza en la tienda.',
                'bullets' => ['Piezas reutilizables, no es un consumible de un solo uso.', 'Sirve para laptop, monitor y teclado.', 'Ocupa poco espacio y cabe en la funda del equipo.'],
            ],
            'mouse' => [
                'corta' => 'Mouse de uso diario, verificado por el área técnica.',
                'sujeto' => 'mouse',
                'intro' => 'Puntero probado en mostrador antes de publicarse, con clics y rueda revisados uno por uno. Es la referencia exacta del fabricante que aparece en el producto.',
                'bullets' => ['Compatible con Windows, macOS y Linux sin instalar controladores.', 'Carcasa con agarre lateral para jornada larga.', 'Viene con su propio empaque de fábrica.'],
            ],
            'teclado' => [
                'corta' => 'Teclado completo con distribución en español.',
                'sujeto' => 'teclado',
                'intro' => 'Teclas de perfil bajo con las letras y la ñ serigrafiadas, no pegatinas. Se revisa tecla por tecla en mostrador antes de publicarlo.',
                'bullets' => ['Conexión directa, sin configuración adicional.', 'Base antideslizante y pies rebatibles.', 'Resistente al uso continuo de oficina y cabinas.'],
            ],
            'combo' => [
                'corta' => 'Conjunto de periféricos coordinados en una sola caja.',
                'sujeto' => 'combo',
                'intro' => 'Periféricos que se venden juntos y comparten diseño, lo que deja el escritorio parejo y evita comprar cada pieza por separado.',
                'bullets' => ['Todos los elementos se prueban antes del cierre de caja.', 'Receptores y cables incluidos.', 'Ideal para equipar un puesto de trabajo completo.'],
            ],
            'pasta_termica' => [
                'corta' => 'Compuesto para mejorar la transferencia de calor del procesador.',
                'sujeto' => 'pasta térmica',
                'intro' => 'Rellena los microporos entre el disipador y la superficie del chip, que es por donde el calor no pasa bien con el aire. Se aplica en capa fina, no en cantidad.',
                'bullets' => ['Presentación en jeringa o frasco según el lote.', 'Para mantenimiento de laptops y PCs de escritorio.', 'Requiere limpieza del disipador antes de reaplicar.'],
            ],
            'case_disco' => [
                'corta' => 'Carcasa que convierte un disco interno en unidad externa.',
                'sujeto' => 'case de disco duro',
                'intro' => 'Toma el disco que ya tienes y lo deja como unidad portátil conectada por USB, sin necesidad de comprar un disco externo nuevo.',
                'bullets' => ['Incluye el cable de conexión.', 'No requiere fuente externa para discos de 2.5 pulgadas.', 'Desmontable, para cambiar el disco cuando quieras.'],
            ],
            'cable_hdmi' => [
                'corta' => 'Cable de video y audio digital para monitor, TV y proyector.',
                'sujeto' => 'cable HDMI',
                'intro' => 'Lleva imagen y sonido por un solo cable entre la laptop y la pantalla externa. Se prueba en mostrador con un monitor real antes de publicarlo.',
                'bullets' => ['Conectores con contacto firme, no sueltos.', 'Largo indicado en la ficha del producto.', 'Compatible con consolas, decodificadores y PCs.'],
            ],
            'cable_vga' => [
                'corta' => 'Cable de video analógico con conectores de tornillo.',
                'sujeto' => 'cable VGA',
                'intro' => 'Sigue siendo la salida de proyectores y monitores antiguos en oficinas y aulas. Los tornillos laterales evitan que el conector se afloje con el movimiento.',
                'bullets' => ['Segueta de video analógica estándar.', 'Probado con monitor antes de publicarse.', 'Ideal para equipos que no tienen HDMI.'],
            ],
            'cable_dvi' => [
                'corta' => 'Cable de video digital para monitores con entrada DVI.',
                'sujeto' => 'cable DVI',
                'intro' => 'Enlace digital para monitores y CPUs de generación anterior, con tornillos de sujeción y contacto estable a resoluciones nativas.',
                'bullets' => ['Revisa el tipo de conector de tu equipo antes de comprar.', 'Se prueba en mostrador.', 'Alternativa cuando el VGA se ve con ruido.'],
            ],
            'cable_impresora' => [
                'corta' => 'Cable de datos USB para impresora.',
                'sujeto' => 'cable de datos para impresora',
                'intro' => 'Conexión USB tipo A a tipo B, la que usan las impresoras de escritorio. Un cable dañado es la causa más común de "la impresora no aparece" en el sistema.',
                'bullets' => ['Contacto firme en ambos extremos.', 'Largo suficiente para poner la impresora en el escritorio.', 'Probado con una impresora real.'],
            ],
            'cable_poder' => [
                'corta' => 'Cable de alimentación para PC y monitor.',
                'sujeto' => 'cable de poder',
                'intro' => 'Lleva corriente desde el enchufe a la fuente de la PC o del monitor. El reemplazo cuesta poco, pero un cable flojo apaga el equipo de golpe y daña la fuente.',
                'bullets' => ['Clavija y conector estándar de 3 pines.', 'Aisla sin calentarse con el uso continuo.', 'Mantén uno de repuesto en el escritorio.'],
            ],
            'cable_trebol' => [
                'corta' => 'Cable de poder con conector trébol.',
                'sujeto' => 'cable de poder trébol',
                'intro' => 'Conector de tres pines en forma de trébol, el que usan monitores, fuentes de PC y algunos cargadores de laptop. Se prueba el contacto antes de publicarlo.',
                'bullets' => ['Encaje firme, sin juego lateral.', 'Revisar el voltaje impreso del equipo antes de usarlo.', 'Repuesto común en oficinas.'],
            ],
            'cable_tipo8' => [
                'corta' => 'Cable de poder con conector tipo 8.',
                'sujeto' => 'cable de poder tipo 8',
                'intro' => 'Conector de dos pines con forma de ocho invertido, muy usado en laptops, consolas y fuentes. Es el reemplazo directo cuando el original se parte o se calienta.',
                'bullets' => ['Contactos limpios y sin óxido.', 'Comprobar la clavija del equipo antes de comprar.', 'Se prueba en mostrador.'],
            ],
            'cable_red' => [
                'corta' => 'Cable de red para conexión por switch o router.',
                'sujeto' => 'cable de red',
                'intro' => 'Conexión por cable donde el WiFi falla: en oficinas con paredes gruesas o cámaras, el cable da la estabilidad que el aire no da. Se testa con probador de pares.',
                'bullets' => ['Conectores RJ45 fichados y prensados.', 'Categoría indicada en la ficha del producto.', 'Largo medido, no aproximado.'],
            ],
            'rj45' => [
                'corta' => 'Conectores RJ45 por caja, para fichar cables de red.',
                'sujeto' => 'conector RJ45',
                'intro' => 'La pieza que se prensa al cable de red. Es consumible: se pierde el extremo al cortar o queda mal prensado, y tener caja evita dejar una instalación a medias.',
                'bullets' => ['Categoría indicada en la ficha.', 'Compatibles con cable trenzado estándar.', 'Se usan con pinza de prensado RJ45.'],
            ],
            'adaptador_wifi' => [
                'corta' => 'Adaptador USB para dar WiFi a PCs de escritorio.',
                'sujeto' => 'adaptador WiFi',
                'intro' => 'Le agrega conexión inalámbrica a un equipo que no tiene tarjeta WiFi o cuya tarjeta interna se dañó. Se conecta al puerto USB y queda listo.',
                'bullets' => ['Antena interna o externa según el modelo.', 'Soporta las bandas que declara el fabricante.', 'Se prueba con un router real antes de publicarlo.'],
            ],
            'adaptador_video' => [
                'corta' => 'Convierte una salida DVI en entrada HDMI.',
                'sujeto' => 'adaptador de video',
                'intro' => 'Une un equipo que solo tiene DVI con un cable o monitor HDMI, sin cambiar el cableado que ya está instalado. Solo pasa video, no audio.',
                'bullets' => ['Conexión pasiva, no necesita energía.', 'Encaje firme en ambos puertos.', 'Verifica los conectores de tus dos equipos.'],
            ],
            'usb_led' => [
                'corta' => 'Memoria USB con iluminación.',
                'sujeto' => 'USB LED',
                'intro' => 'Pendrive que se enciende al conectarse, útil para localizarlo en un puerto trasero del CPU o en la oscuridad de una cabina.',
                'bullets' => ['Capacidad indicada en la ficha.', 'Formato y contenido verificados en mostrador.', 'Compatible con Windows y macOS.'],
            ],
            'soporte' => [
                'corta' => 'Base autoadhesiva que eleva la laptop.',
                'sujeto' => 'soporte para laptop',
                'intro' => 'Levanta el equipo unos grados, que es lo que baja la temperatura y acomoda la pantalla a la altura de los ojos. Se pega al chasis y queda fijo.',
                'bullets' => ['Adhesivo de alta adherencia, no se despega con el calor.', 'No bloquea las ventilaciones inferiores.', 'Se puede retirar con calor suave.'],
            ],
            'cargador' => [
                'corta' => 'Cargador de laptop con la punta que corresponde al equipo.',
                'sujeto' => 'cargador',
                'intro' => 'La punta es lo que decide si sirve: dos cargadores del mismo voltaje no sirven si la punta no encaja. En la ficha va la descripción exacta de la punta y en mostrador se prueba el contacto.',
                'bullets' => ['Voltaje y amperaje impresos en la etiqueta del cargador.', 'Cable de poder incluido.', 'Revisa la medida de la punta de tu laptop antes de comprar.'],
            ],
            'bateria' => [
                'corta' => 'Batería interna con el código de referencia del equipo.',
                'sujeto' => 'batería',
                'intro' => 'Reemplazo identificado por el código impreso en la batería original, que es la forma segura de comprar la que encaja. Se revisa el conector antes de publicarla.',
                'bullets' => ['Código de referencia visible en la ficha.', 'Celdas sin ciclos de uso previo.', 'Se recomienda instalación por técnico.'],
            ],
            'cooler_laptop' => [
                'corta' => 'Base refrigerante externa para laptop.',
                'sujeto' => 'cooler para laptop',
                'intro' => 'Ventilador externo que empuja aire a la rejilla inferior del equipo, donde la limpieza interna no alcanza. Baja la temperatura en sesiones largas de render o juego.',
                'bullets' => ['Se alimenta por USB.', 'Altura ajustable según el lote.', 'Revisa el ancho de tu laptop contra la base.'],
            ],
            'cooler_pc' => [
                'corta' => 'Ventilador interno para el gabinete de la PC.',
                'sujeto' => 'cooler de gabinete',
                'intro' => 'Mueve el aire caliente hacia afuera del gabinete. Es la pieza que decide si los componentes trabajan a temperatura estable o empiezan a bajar el rendimiento.',
                'bullets' => ['Conexión indicada en la ficha del producto.', 'Iluminación incluida cuando el modelo la declara.', 'Revisa el tamaño del alojamiento antes de comprar.'],
            ],
            'fuente' => [
                'corta' => 'Fuente de poder para gabinete de PC de escritorio.',
                'sujeto' => 'fuente de poder',
                'intro' => 'Entrega los voltajes que consumen placa, CPU y discos. Una fuente que no da la potencia real declarada apaga el equipo o quema componentes, por eso se publica la potencia verificada.',
                'bullets' => ['Cables y conectores según el estándar ATX.', 'Protecciones contra sobrecarga.', 'Certificación de eficiencia cuando el modelo la tiene.'],
            ],
            'ram' => [
                'corta' => 'Memoria para laptop o escritorio del estándar indicado.',
                'sujeto' => 'memoria RAM',
                'intro' => 'Ampliar memoria es el cambio que más se nota cuando el equipo tiene muchas pestañas abiertas o edita archivos pesados. La generación debe coincidir con la placa, no es intercambiable.',
                'bullets' => ['Capacidad y tecnología indicadas en la ficha.', 'Contactos limpios, sin marcas de uso.', 'Verifica el tipo que admite tu placa antes de comprar.'],
            ],
            'parlante' => [
                'corta' => 'Parlante externo de escritorio.',
                'sujeto' => 'parlante',
                'intro' => 'Audio externo para clases, llamadas y contenido, que es donde los altavoces de la laptop se quedan cortos. Se conecta por el puerto indicado en la ficha.',
                'bullets' => ['Color según el lote de la referencia.', 'Alimentación por USB.', 'Control de volumen en el propio parlante.'],
            ],
            'tinta_original' => [
                'corta' => 'Tinta original del fabricante para recarga.',
                'sujeto' => 'tinta original',
                'intro' => 'Consumible empaquetado por el propio fabricante de la impresora, que es la forma de conservar la garantía del equipo y el color declarado por la marca.',
                'bullets' => ['Código de la botella impreso en el empaque.', 'Color indicado en la ficha.', 'Cerrar bien el envase después de cada recarga.'],
            ],
            'tinta_compatible' => [
                'corta' => 'Tinta alternativa para recarga de tanque.',
                'sujeto' => 'tinta compatible',
                'intro' => 'Recarga equivalente a la referencia original, usada cuando el volumen de impresión hace caro el consumible de marca. Se vende con la referencia que identifica la impresora compatible.',
                'bullets' => ['Referencia de la impresora indicada en la ficha.', 'Color según el lote.', 'Se recomienda no mezclar marcas dentro del mismo tanque.'],
            ],
            'tinta_litro' => [
                'corta' => 'Tinta por litro para equipos de tanque y UV.',
                'sujeto' => 'tinta de litro',
                'intro' => 'Presentación a granel para quien imprime en volumen, donde el costo por página baja de forma notoria frente al envase pequeño.',
                'bullets' => ['Fórmula indicada en la ficha.', 'Color según la referencia.', 'Manejar con guantes y evitar el contacto con la piel.'],
            ],
            'caja_mantenimiento' => [
                'corta' => 'Caja de mantenimiento (waste ink tank) de la impresora.',
                'sujeto' => 'caja de mantenimiento',
                'intro' => 'Recoge el tinta que la impresora expulsa durante las limpiezas automáticas. Cuando se llena, el equipo se bloquea y pide reemplazo: no es una falla, es consumible.',
                'bullets' => ['Código de la caja impreso en el empaque.', 'Reemplazo sin herramienta especial.', 'Se recomienda cambiarla con la impresora encendida y luego reiniciarla.'],
            ],
            'goma_arrastre' => [
                'corta' => 'Rueda de goma que arrastra el papel.',
                'sujeto' => 'goma de arrastre',
                'intro' => 'La pieza que toma la hoja desde la bandeja. Cuando se desgasta, la impresora jala de a dos hojas o reporta atasco aunque no haya papel mal puesto.',
                'bullets' => ['Presentación indicada en la ficha.', 'Limpieza con paño seco antes de instalar.', 'Se cambia sin desarmar la estructura del equipo.'],
            ],
            'almohadilla' => [
                'corta' => 'Almohadilla absorbente de tinta residual.',
                'sujeto' => 'almohadilla',
                'intro' => 'Esponja que retiene la tinta de las limpiezas de cabezales. Saturada, la impresora muestra el aviso de "final de vida útil" y deja de imprimir.',
                'bullets' => ['Serie de la impresora indicada en la ficha.', 'Se reemplaza junto con el reseteo del contador.', 'Manipular con guantes: la tinta residual mancha.'],
            ],
            'antivirus' => [
                'corta' => 'Licencia de antivirus por dispositivos.',
                'sujeto' => 'licencia de antivirus',
                'intro' => 'Protección con actualización de definiciones, que es lo que un sistema sin antivirus no hace por sí solo. Se entrega la clave de activación para registrarlo en la cuenta del fabricante.',
                'bullets' => ['Cantidad de dispositivos indicada en la ficha.', 'Vigencia declarada por el fabricante.', 'La activación se hace con tu propio correo, no compartimos cuentas.'],
            ],
            default => [
                'corta' => 'Accesorio de stock físico verificado en tienda.',
                'sujeto' => 'accesorio',
                'intro' => 'Pieza de stock verificada en tienda antes de publicarse, con la referencia interna del conteo de almacén.',
                'bullets' => ['Se entrega en su presentación original.', 'Compatible con el equipo indicado en la ficha.', 'Consulta por WhatsApp si necesitas más unidades.'],
            ],
        };
    }
}
