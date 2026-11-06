<?php

namespace App\Support;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Convierte una lista pegada desde Excel (una fila por producto) en productos
 * del catálogo compartido GALY + Tectron.
 *
 * Formato de cada fila, separada por tabulación (también acepta ; o |):
 *
 *   DESCRIPCIÓN | MODELO | CANTIDAD | PRECIO COMPRA | PRECIO VENTA | CATEGORÍA
 *
 * La CATEGORÍA es opcional: si no viene, se deduce del nombre. También se
 * acepta la fila numerada del Excel (1, 2, 3...) y el formato de seis columnas
 * con "PRECIO + IGV" en medio, que se ignora.
 */
class ProductoImportador
{
    /** @var array<string, string> palabra clave normalizada => nombre de categoría */
    private const CATEGORIAS_POR_NOMBRE = [
        'PROTECTOR DE TECLADO' => 'PROTECTOR DE TECLADO',
        'PROTECTOR DE PANTALLA' => 'PROTECTOR DE PANTALLA',
        'PAD MOUSE' => 'PAD MOUSE',
        'FUNDA' => 'FUNDAS',
        'MOCHILA' => 'MOCHILA',
        'SKIN' => 'SKIN',
        'KIT DE LIMPIEZA' => 'KIT',
        'PASTA TERMICA' => 'PASTA',
        'CASE DE DISCO' => 'CASE DE DISCO',
        'CAJA DE MANTENIMIENTO' => 'CAJA DE MANTENIMIENTO',
        'GOMA DE ARRASTRE' => 'GOMA DE ARRASTRE',
        'ALMOHADILLA' => 'ALMOHADILLA',
        'ANTIVIRUS' => 'ANTIVIRUS',
        'TINTA' => 'TINTA DE IMPRESORA',
        'RJ45' => 'Redes',
        'CABLE DE RED' => 'Redes',
        'DIXON' => 'Redes',
        'CARGADOR' => 'CARAGADOR',
        'CARGADRO' => 'CARAGADOR',
        'COMBO' => 'COMBO DE TECLADO',
        'TECLADO' => 'TECLADO',
        'MOUSE' => 'Mouses',
        'BATERIA' => 'BACTERIA',
        'ADAPTADOR' => 'ADAPTADOR',
        'FUENTE' => 'FUENTE',
        'COOLER' => 'COOLER',
        'COOLE' => 'COOLER',
        'PARLANTE' => 'Parlantes',
        'MEMORIA RAM' => 'Componentes',
        'USB' => 'USB',
        'SOPORTE' => 'Accesorios',
        'CABLE' => 'CABLE',
    ];

    /**
     * Lee el texto pegado y devuelve filas limpias.
     *
     * @return array<int, array{descripcion: string, modelo: string, cantidad: int, pc: float, pv: float, categoria: string}>
     */
    public static function filas(string $texto): array
    {
        $filas = [];

        foreach (preg_split('/\R/u', $texto) ?: [] as $linea) {
            $linea = trim($linea);
            if ($linea === '' || self::esEncabezado($linea)) {
                continue;
            }

            $celdas = self::celdas($linea);
            if ($celdas === null) {
                continue;
            }

            // Excel suele arrastrar el número de fila como primera columna.
            if (count($celdas) >= 4 && preg_match('/^\d+$/', $celdas[0])) {
                array_shift($celdas);
            }

            $total = count($celdas);
            if ($total < 2 || $celdas[0] === '') {
                continue;
            }

            $descripcion = $celdas[0];
            $modelo = '';
            $cantidad = 1;
            $pc = 0.0;
            $pv = 0.0;
            $categoria = '';

            if ($total === 2) {
                if (is_numeric($celdas[1])) {
                    $cantidad = (int) self::numero($celdas[1]);
                } else {
                    $modelo = $celdas[1];
                }
            } else {
                $modelo = $celdas[1];
                $cantidad = (int) self::numero($celdas[2]);

                if ($total === 4) {
                    $pv = self::numero($celdas[3]);
                } elseif ($total === 5) {
                    $pc = self::numero($celdas[3]);
                    $pv = self::numero($celdas[4]);
                } else {
                    // 6 o más: se ignoran las columnas sobrantes del final.
                    $ultima = $celdas[$total - 1];
                    if (is_numeric($ultima)) {
                        // Formato del Excel: precio, precio + IGV, precio venta.
                        $pc = self::numero($celdas[3]);
                        $pv = self::numero($ultima);
                    } else {
                        // Formato: precio compra, precio venta, categoría.
                        $pc = self::numero($celdas[3]);
                        $pv = self::numero($celdas[4]);
                        $categoria = $ultima;
                    }
                }
            }

            if ($descripcion === '') {
                continue;
            }

            $filas[] = [
                'descripcion' => $descripcion,
                'modelo' => $modelo,
                'cantidad' => max(0, $cantidad),
                'pc' => max(0, $pc),
                'pv' => max(0, $pv),
                'categoria' => $categoria,
            ];
        }

        return $filas;
    }

    /**
     * Cruza las filas con las categorías, marcas y productos que ya existen.
     * No escribe nada: sirve para la previsualización.
     *
     * @param  array<int, array{descripcion: string, modelo: string, cantidad: int, pc: float, pv: float, categoria: string}>  $filas
     * @return array<int, array<string, mixed>>
     */
    public static function preparar(array $filas, ?int $categoriaDefecto = null, ?int $marcaDefecto = null): array
    {
        $categorias = Categoria::all();
        $marcas = Marca::all();

        $existentes = Producto::withTrashed()->get(['id', 'nombre', 'codigo']);
        $porNombre = $existentes->keyBy(fn (Producto $p) => self::normal($p->nombre));
        $porCodigo = $existentes->keyBy(fn (Producto $p) => self::normal($p->codigo));
        $codigosUsados = $existentes->pluck('codigo')->filter()->map(fn ($c) => self::normal($c))->all();

        $preparadas = [];

        foreach ($filas as $fila) {
            $nombre = self::nombreCompleto($fila['descripcion'], $fila['modelo']);
            $categoria = self::categoriaDe($fila['categoria'], $nombre, $categorias, $categoriaDefecto);
            $marca = self::marcaDe($nombre, $marcas, $marcaDefecto);
            $codigo = self::codigoUnico($nombre, $codigosUsados);
            $codigosUsados[] = self::normal($codigo);

            $existente = $porCodigo[self::normal($codigo)] ?? $porNombre[self::normal($nombre)] ?? null;

            $avisos = [];
            if (! $categoria) {
                $avisos[] = 'falta categoría';
            }
            if ($fila['pv'] <= 0) {
                $avisos[] = 'falta precio de venta';
            }

            $preparadas[] = [
                'descripcion' => $fila['descripcion'],
                'nombre' => $nombre,
                'modelo' => $fila['modelo'],
                'cantidad' => $fila['cantidad'],
                'pc' => $fila['pc'],
                'pv' => $fila['pv'],
                'categoria' => $categoria,
                'marca' => $marca,
                'codigo' => $codigo,
                'existente' => $existente?->id,
                'existente_codigo' => $existente?->codigo,
                'avisos' => $avisos,
            ];
        }

        return $preparadas;
    }

    /**
     * Crea los productos nuevos y, si se pide, completa stock y precio de los
     * que ya existen con el mismo nombre o código.
     *
     * @param  array<int, array<string, mixed>>  $preparadas
     * @return array{creados: int, actualizados: int, omitidos: int, motivos: array<int, string>}
     */
    public static function guardar(array $preparadas, bool $actualizar = false): array
    {
        $creados = $actualizados = $omitidos = 0;
        $motivos = [];

        foreach ($preparadas as $fila) {
            if ($fila['avisos'] !== []) {
                $omitidos++;
                $motivos[] = $fila['nombre'].': '.implode(', ', $fila['avisos']).'.';

                continue;
            }

            if ($fila['existente'] !== null) {
                if ($actualizar) {
                    $producto = Producto::withTrashed()->find($fila['existente']);
                    if ($producto) {
                        $producto->stock = $fila['cantidad'];
                        if ($fila['pc'] > 0) {
                            $producto->precio_compra = $fila['pc'];
                        }
                        if ($fila['pv'] > 0) {
                            $producto->precio_venta = $fila['pv'];
                            $producto->precio_oferta = $fila['pv'];
                        }
                        $producto->categoria_id = $producto->categoria_id ?: $fila['categoria'];
                        $producto->marca_id = $producto->marca_id ?: $fila['marca'];
                        $columnas = $producto->getConnection()->getSchemaBuilder()->getColumnListing('productos');
                        if (in_array('disponible', $columnas, true)) {
                            $producto->disponible = $fila['cantidad'] > 0;
                        }
                        $producto->deleted_at = null;
                        $producto->save();
                        $actualizados++;
                    } else {
                        $omitidos++;
                        $motivos[] = $fila['nombre'].': no se pudo abrir el producto existente.';
                    }
                } else {
                    $omitidos++;
                    $motivos[] = $fila['nombre'].': ya existe ('.$fila['existente_codigo'].') y no se pidió actualizar.';
                }

                continue;
            }

            $producto = new Producto;
            $producto->fill([
                'categoria_id' => $fila['categoria'],
                'marca_id' => $fila['marca'],
                'nombre' => $fila['nombre'],
                'codigo' => $fila['codigo'],
                'descripcion_corta' => Str::limit(self::descripcionCorta($fila), 250),
                'descripcion_larga' => self::descripcionLarga($fila),
                'detalles_tecnicos' => self::detalles($fila),
                'precio_compra' => $fila['pc'],
                'precio_venta' => $fila['pv'],
                'precio_oferta' => $fila['pv'],
                'stock' => $fila['cantidad'],
            ]);

            // Columnas que existen en la tabla compartida de GALY pero no en
            // la base de pruebas (que usa el esquema mínimo de las migraciones).
            $columnas = $producto->getConnection()->getSchemaBuilder()->getColumnListing('productos');
            if (in_array('marca', $columnas, true)) {
                $producto->marca = $fila['marca'] ? (Marca::find($fila['marca'])?->nombre ?? '') : '';
            }
            if (in_array('categoria_nombre', $columnas, true)) {
                $producto->categoria_nombre = Categoria::find($fila['categoria'])?->nombre;
            }
            if (in_array('disponible', $columnas, true)) {
                $producto->disponible = $fila['cantidad'] > 0;
            }
            $producto->save();

            $creados++;
        }

        return compact('creados', 'actualizados', 'omitidos', 'motivos');
    }

    private static function esEncabezado(string $linea): bool
    {
        $normal = self::normal($linea);

        return str_starts_with($normal, 'descripcion') || str_starts_with($normal, 'producto');
    }

    /**
     * @return array<int, string>|null
     */
    private static function celdas(string $linea): ?array
    {
        $separador = match (true) {
            str_contains($linea, "\t") => "\t",
            str_contains($linea, ';') => ';',
            str_contains($linea, '|') => '|',
            default => null,
        };

        if ($separador === null) {
            return null;
        }

        return array_map(
            fn ($celda) => trim(trim((string) $celda), "\"'"),
            explode($separador, $linea)
        );
    }

    private static function numero(mixed $valor): float
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return 0.0;
        }

        $valor = preg_replace('/[^\d,.\-]/u', '', $valor) ?? '';

        // "1,234.56" (miles con coma) vs "15,50" (coma decimal).
        if (str_contains($valor, ',') && str_contains($valor, '.')) {
            $valor = str_replace(',', '', $valor);
        } else {
            $valor = str_replace(',', '.', $valor);
        }

        return is_numeric($valor) ? (float) $valor : 0.0;
    }

    private static function nombreCompleto(string $descripcion, string $modelo): string
    {
        $nombre = trim(preg_replace('/\s+/u', ' ', $descripcion) ?? $descripcion);
        $modelo = trim($modelo);

        if ($modelo !== '' && ! str_contains(self::normal($nombre), self::normal($modelo))) {
            $nombre .= ' '.$modelo;
        }

        return Str::limit($nombre, 250, '');
    }

    /**
     * @param  Collection<int, Categoria>  $categorias
     */
    private static function categoriaDe(string $texto, string $nombre, Collection $categorias, ?int $defecto): ?int
    {
        $buscada = self::normal($texto);
        if ($buscada !== '') {
            $encontrada = $categorias->first(fn (Categoria $c) => self::normal($c->nombre) === $buscada || self::normal($c->slug) === $buscada);
            if ($encontrada) {
                return $encontrada->id;
            }
        }

        $normal = self::normal($nombre);
        foreach (self::CATEGORIAS_POR_NOMBRE as $clave => $categoria) {
            if (str_contains($normal, self::normal($clave))) {
                $encontrada = $categorias->first(fn (Categoria $c) => self::normal($c->nombre) === self::normal($categoria));
                if ($encontrada) {
                    return $encontrada->id;
                }
            }
        }

        return $defecto;
    }

    /**
     * @param  Collection<int, Marca>  $marcas
     */
    private static function marcaDe(string $nombre, Collection $marcas, ?int $defecto): ?int
    {
        $normal = self::normal($nombre);
        $encontrada = $marcas
            ->sortByDesc(fn (Marca $m) => mb_strlen($m->nombre))
            ->first(fn (Marca $m) => $m->nombre !== '' && str_contains($normal, self::normal($m->nombre)));

        return $encontrada?->id ?? $defecto;
    }

    /**
     * @param  array<int, string>  $usados
     */
    private static function codigoUnico(string $nombre, array $usados): string
    {
        $base = 'TEC-IMP-'.Str::upper(Str::slug(Str::limit($nombre, 45, ''), '-'));
        $base = Str::limit($base, 90, '');
        $codigo = $base;
        $contador = 2;

        while (in_array(self::normal($codigo), $usados, true)) {
            $codigo = $base.'-'.$contador;
            $contador++;
        }

        return $codigo;
    }

    /**
     * @param  array<string, mixed>  $fila
     */
    private static function descripcionCorta(array $fila): string
    {
        $texto = $fila['nombre'].'.';
        if ($fila['modelo'] !== '') {
            $texto .= ' Modelo '.$fila['modelo'].'.';
        }

        return $texto.' Stock físico verificado en tienda.';
    }

    /**
     * @param  array<string, mixed>  $fila
     */
    private static function descripcionLarga(array $fila): string
    {
        return '<p>'.$fila['nombre'].' disponible en Tectron. Producto de stock físico verificado antes de publicarse; '
            .'la cantidad que se muestra es la que tenemos en almacén en este momento.</p>';
    }

    /**
     * @param  array<string, mixed>  $fila
     */
    private static function detalles(array $fila): string
    {
        $filas = ['Producto' => $fila['nombre']];
        if ($fila['modelo'] !== '') {
            $filas['Modelo'] = $fila['modelo'];
        }
        $filas['Referencia interna'] = $fila['codigo'];
        $filas['Unidades en almacén'] = (string) $fila['cantidad'];

        return '<table>'.collect($filas)
            ->map(fn ($valor, $clave) => '<tr><td>'.e($clave).'</td><td>'.e($valor).'</td></tr>')
            ->implode('').'</table>';
    }

    private static function normal(?string $valor): string
    {
        $valor = Str::of((string) $valor)->lower()->ascii()->value();

        return preg_replace('/\s+/', ' ', trim($valor)) ?? '';
    }
}
