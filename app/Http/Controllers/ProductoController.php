<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\Asesor;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\ProductoImagen;
use App\Support\ProductoImportador;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $ajuste = Ajuste::first();
        $enPapelera = $request->boolean('eliminados');
        $enAgotados = $request->boolean('agotados');
        $enBajoStock = $request->boolean('bajo_stock');
        $query = $this->busqueda($request->input('buscar'))
            ->when($enPapelera, fn ($consulta) => $consulta->onlyTrashed())
            ->when($enAgotados && ! $enPapelera, fn ($consulta) => $consulta->where('stock', '<=', 0))
            ->when($enBajoStock && ! $enPapelera && ! $enAgotados, fn ($consulta) => $consulta->where('stock', '>', 0)->where('stock', '<=', 10))
            ->with('categoria');
        $hasProductMap = Schema::hasTable('tectron_galy_product_map');

        if ($hasProductMap) {
            $query->select('productos.*')->selectSub(
                DB::table('tectron_galy_product_map')
                    ->selectRaw('1')
                    ->whereColumn('galy_product_id', 'productos.id')
                    ->limit(1),
                'es_producto_tectron'
            )->orderByDesc('es_producto_tectron')
                ->orderBy('productos.nombre');
        }

        $productos = $query->paginate(10)->withQueryString();
        $productosGaly = Producto::count();
        $productosEnPapelera = Producto::onlyTrashed()->count();
        $productosAgotados = Producto::where('stock', '<=', 0)->count();
        $productosBajoStock = Producto::where('stock', '>', 0)->where('stock', '<=', 10)->count();
        $productosTectron = $hasProductMap
            ? DB::table('tectron_galy_product_map')->count()
            : 0;

        return view('admin.productos.index', compact(
            'productos',
            'ajuste',
            'productosGaly',
            'productosTectron',
            'productosEnPapelera',
            'productosAgotados',
            'productosBajoStock',
            'enPapelera',
            'enAgotados',
            'enBajoStock',
            'hasProductMap'
        ));
    }

    /**
     * Repone stock rápidamente desde el listado: suma la cantidad indicada (por
     * defecto 10) y reactiva el producto si vuelve a tener stock.
     */
    public function reponerStock(Request $request, $id)
    {
        $producto = Producto::findOrFail($id);
        $cantidad = (int) $request->input('cantidad', 10);
        if ($cantidad < 1) {
            $cantidad = 10;
        }
        $producto->stock = (int) $producto->stock + $cantidad;
        $producto->disponible = (int) $producto->stock > 0;
        $producto->save();

        return redirect()->back()
            ->with('success', 'Stock repuesto: ' . $producto->nombre . ' ahora tiene ' . $producto->stock . ' unidades.');
    }

    /**
     * La misma búsqueda del listado, sin paginar: la necesita el borrado por
     * selección ("los N del filtro") para no confiar en los ids que trae el
     * formulario.
     */
    private function busqueda(?string $buscar): \Illuminate\Database\Eloquent\Builder
    {
        $query = Producto::query();

        if ($buscar !== null && trim($buscar) !== '') {
            $query->where(function ($consulta) use ($buscar) {
                $consulta->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('codigo', 'like', "%{$buscar}%")
                    ->orWhere('descripcion_corta', 'like', "%{$buscar}%")
                    ->orWhere('descripcion_larga', 'like', "%{$buscar}%");
            });
        }

        return $query;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categorias = Categoria::all();
        $marcas = Marca::where('activo', true)->get();

        return view('admin.productos.create', compact('categorias', 'marcas'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'codigo' => ['required', 'string', 'max:100', Rule::unique('galy.productos', 'codigo')],
            'descripcion_corta' => 'required|string|max:500',
            'descripcion_larga' => 'required|string',
            'detalles_tecnicos' => 'nullable|string',
            'precio_compra' => 'required|numeric|min:0',
            'precio_venta' => 'required|numeric|min:0',
            'precio_oferta' => 'nullable|numeric|min:0',
            'descuento' => 'nullable|integer|min:0|max:100',
            'stock' => 'required|numeric|min:0',
            'categoria_id' => ['required', Rule::exists('galy.categorias', 'id')],
            'marca_id' => ['nullable', Rule::exists('galy.marcas', 'id')],
            'destacado' => 'nullable|boolean',
            'nuevo' => 'nullable|boolean',
            'recomendado' => 'nullable|boolean',
            'mas_vendido' => 'nullable|boolean',
        ]);

        $categoria = Categoria::findOrFail($request->categoria_id);
        $marca = $request->filled('marca_id') ? Marca::findOrFail($request->marca_id) : null;
        $producto = new Producto;
        $producto->nombre = $request->nombre;
        $producto->marca = $marca?->nombre ?? '';
        $producto->categoria_nombre = $categoria->nombre;
        $producto->codigo = $request->codigo;
        $producto->descripcion_corta = $request->descripcion_corta;
        $producto->descripcion_larga = $request->descripcion_larga;
        $producto->descripcion = $request->descripcion_corta;
        $producto->detalles_tecnicos = Producto::sanitizeHtml($request->detalles_tecnicos);
        $producto->precio_compra = $request->precio_compra;
        $producto->precio_venta = $request->precio_venta;
        $producto->precio_oferta = $request->precio_oferta;
        $producto->precio_oferta_manual = $request->precio_oferta;
        $producto->descuento = $request->descuento;
        $producto->en_oferta = $request->filled('precio_oferta') && $request->precio_oferta > 0;
        $producto->stock = $request->stock;
        $producto->disponible = (int) $request->stock > 0;
        $producto->categoria_id = $request->categoria_id;
        $producto->marca_id = $request->marca_id;

        $producto->destacado = $request->has('destacado');
        $producto->nuevo = $request->has('nuevo');
        $producto->recomendado = $request->has('recomendado');
        $producto->mas_vendido = $request->has('mas_vendido');

        $producto->save();

        return redirect()->route('admin.productos.index')
            ->with('mensaje', 'Producto creado exitosamente')
            ->with('icono', 'success');
    }

    /**
     * Formulario para pegar la lista de inventario y crear los productos en lote.
     */
    public function importar()
    {
        return view('admin.productos.importar', [
            'categorias' => Categoria::orderBy('nombre')->get(),
            'marcas' => Marca::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    /**
     * Previsualiza o guarda la lista pegada en el formulario de importación.
     */
    public function importarGuardar(Request $request)
    {
        $datos = $request->validate([
            'filas' => ['required', 'string'],
            'categoria_id' => ['nullable', 'integer'],
            'marca_id' => ['nullable', 'integer'],
            'accion' => ['required', Rule::in(['previsualizar', 'importar'])],
            'actualizar' => ['nullable', 'boolean'],
        ], [
            'filas.required' => 'Pega al menos una fila de la lista.',
            'accion.required' => 'Indica si quieres previsualizar o importar.',
        ]);

        $filas = ProductoImportador::filas($datos['filas']);

        if ($filas === []) {
            return back()->withInput()->withErrors([
                'filas' => 'No se reconoció ninguna fila. Separa las columnas con tabulación (al pegar desde Excel) y revisa que la lista no esté vacía.',
            ]);
        }

        $categoriaDefecto = $request->filled('categoria_id') && Categoria::whereKey($request->input('categoria_id'))->exists()
            ? (int) $request->input('categoria_id')
            : null;
        $marcaDefecto = $request->filled('marca_id') && Marca::whereKey($request->input('marca_id'))->exists()
            ? (int) $request->input('marca_id')
            : null;

        $preparadas = ProductoImportador::preparar($filas, $categoriaDefecto, $marcaDefecto);

        if ($datos['accion'] === 'previsualizar') {
            return view('admin.productos.importar', [
                'categorias' => Categoria::orderBy('nombre')->get(),
                'marcas' => Marca::where('activo', true)->orderBy('nombre')->get(),
                'previa' => $preparadas,
                'filasTexto' => $datos['filas'],
                'categoriaSeleccionada' => $categoriaDefecto,
                'marcaSeleccionada' => $marcaDefecto,
                'actualizar' => $request->boolean('actualizar'),
            ]);
        }

        $resumen = ProductoImportador::guardar($preparadas, $request->boolean('actualizar'));

        $mensaje = $resumen['creados'].' productos creados, '.$resumen['actualizados'].' actualizados y '.$resumen['omitidos'].' omitidos.';

        return redirect()->route('admin.productos.index')
            ->with('mensaje', $mensaje)
            ->with('icono', $resumen['creados'] > 0 || $resumen['actualizados'] > 0 ? 'success' : 'warning')
            ->with('importacion_motivos', $resumen['motivos']);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $producto = Producto::findOrFail($id);

        return view('admin.productos.show', compact('producto'));
    }

    public function imagenes($id)
    {
        $producto = Producto::with('categoria', 'imagenes')->findOrFail($id);

        return view('admin.productos.imagenes', compact('producto'));
    }

    public function upload_imagen(Request $request, $id)
    {
        $uploadedImage = $request->file('imagen');
        $imageInfo = $uploadedImage instanceof UploadedFile && $uploadedImage->isValid()
            ? @getimagesize($uploadedImage->getPathname())
            : false;

        $request->validate([
            'imagen' => [
                'required',
                'file',
                'max:2048',
                function (string $attribute, mixed $value, \Closure $fail) use ($imageInfo): void {
                    $allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
                    if ($imageInfo === false || ! in_array($imageInfo[2] ?? null, $allowedTypes, true)) {
                        $fail('El archivo debe ser una imagen JPEG, PNG, GIF o WebP válida.');
                    }
                },
            ],
        ], [
            'imagen.required' => 'Selecciona una imagen antes de guardar.',
            'imagen.file' => 'No se recibió un archivo de imagen válido.',
            'imagen.max' => 'La imagen no puede pesar más de 2 MB. Redúcela e inténtalo otra vez.',
            'imagen.uploaded' => 'La imagen no se pudo subir. Verifica que pese menos de 2 MB.',
        ]);

        $producto = Producto::findOrFail($id);
        $directory = rtrim((string) config('galy.images_path'), '\\/');
        if ($directory === '') {
            throw new \RuntimeException('GALY_IMAGES_PATH no está configurado.');
        }

        File::ensureDirectoryExists($directory);
        $extension = match ($imageInfo[2] ?? null) {
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_GIF => 'gif',
            IMAGETYPE_WEBP => 'webp',
            default => throw new \RuntimeException('El formato de imagen no es compatible con GALY.'),
        };
        $filename = 'tectron-product-' . $producto->id . '-' . Str::uuid() . '.' . $extension;
        $savedFile = $uploadedImage->move($directory, $filename);
        try {
            DB::connection('galy')->transaction(function () use ($producto, $filename) {
                $imagenProducto = new ProductoImagen;
                $imagenProducto->producto_id = $producto->id;
                $imagenProducto->imagen = $filename;
                $imagenProducto->save();

                $producto->setAttribute('imagen_url', $filename);
                $producto->save();
            });
        } catch (\Throwable $exception) {
            if (is_file($savedFile->getPathname()) && ! unlink($savedFile->getPathname())) {
                report(new \RuntimeException('No se pudo limpiar una imagen GALY tras fallar el registro.', 0, $exception));
            }

            throw $exception;
        }

        $this->sincronizarImagenesSecundarias($producto->fresh('imagenes'));

        return redirect()->route('admin.productos.imagenes', $producto->id)
            ->with('mensaje', 'Imagen subida correctamente. Ahora es la imagen principal del producto.')
            ->with('icono', 'success');
    }

    public function destroy_imagen(Request $request, $id)
    {
        $imagenProducto = ProductoImagen::findOrFail($id);
        $productoId = $imagenProducto->producto_id;
        $imagePath = basename((string) $imagenProducto->imagen);
        $imageProduct = Producto::findOrFail($productoId);
        $wasPrimary = basename((string) $imageProduct->imagen_url) === $imagePath;

        DB::connection('galy')->transaction(function () use ($imagenProducto, $imageProduct, $productoId, $wasPrimary) {
            $imagenProducto->delete();

            if ($wasPrimary) {
                $nextImage = ProductoImagen::where('producto_id', $productoId)->first();
                $imageProduct->setAttribute('imagen_url', $nextImage?->imagen);
                $imageProduct->save();
            }
        });

        $this->sincronizarImagenesSecundarias($imageProduct->fresh('imagenes'));

        return redirect()->route('admin.productos.imagenes', $productoId)
            ->with('mensaje', 'Imagen eliminada correctamente.')
            ->with('icono', 'success');
    }

    /**
     * El admin de la app GALY lee la galería desde `productos.imagenes_secundarias`
     * (texto separado por comas), no desde `producto_imagenes`. Mantener ambos
     * campos sincronizados evita que una foto subida en la web desaparezca allí.
     */
    private function sincronizarImagenesSecundarias(Producto $producto): void
    {
        $conexion = $producto->getConnectionName() ?? config('database.default');
        if (! Schema::connection($conexion)->hasColumn('productos', 'imagenes_secundarias')) {
            return;
        }

        $principal = basename((string) $producto->imagen_url);
        $secundarias = $producto->imagenes
            ->map(fn (ProductoImagen $imagen) => basename((string) $imagen->imagen))
            ->filter(fn (string $imagen) => $imagen !== '' && $imagen !== $principal)
            ->unique()
            ->values()
            ->implode(',');

        if ((string) $producto->imagenes_secundarias !== $secundarias) {
            $producto->setAttribute('imagenes_secundarias', $secundarias);
            $producto->save();
        }
    }

    /**
     * La ficha pública muestra la imagen marcada como principal. Sin esto, al
     * subir una foto nueva la tienda seguía enseñando la primera de la galería.
     */
    public function marcar_imagen_principal(Request $request, $id)
    {
        $imagenProducto = ProductoImagen::findOrFail($id);
        $producto = Producto::findOrFail($imagenProducto->producto_id);

        $producto->setAttribute('imagen_url', $imagenProducto->imagen);
        $producto->save();

        $this->sincronizarImagenesSecundarias($producto->fresh('imagenes'));

        return redirect()->route('admin.productos.imagenes', $producto->id)
            ->with('mensaje', 'Imagen principal actualizada. Ya se muestra en la tienda.')
            ->with('icono', 'success');
    }

    public function edit($id)
    {
        $producto = Producto::findOrFail($id);
        $categorias = Categoria::all();
        $marcas = Marca::where('activo', true)->get();

        return view('admin.productos.edit', compact('producto', 'categorias', 'marcas'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'codigo' => ['required', 'string', 'max:100', Rule::unique('galy.productos', 'codigo')->ignore($id)],
            'descripcion_corta' => 'required|string|max:500',
            'descripcion_larga' => 'required|string',
            'detalles_tecnicos' => 'nullable|string',
            'precio_compra' => 'required|numeric|min:0',
            'precio_venta' => 'required|numeric|min:0',
            'precio_oferta' => 'nullable|numeric|min:0',
            'descuento' => 'nullable|integer|min:0|max:100',
            'stock' => 'required|numeric|min:0',
            'categoria_id' => ['required', Rule::exists('galy.categorias', 'id')],
            'marca_id' => ['nullable', Rule::exists('galy.marcas', 'id')],
            'destacado' => 'nullable|boolean',
            'nuevo' => 'nullable|boolean',
            'recomendado' => 'nullable|boolean',
            'mas_vendido' => 'nullable|boolean',
        ]);

        $producto = Producto::findOrFail($id);
        $categoria = Categoria::findOrFail($request->categoria_id);
        $marca = $request->filled('marca_id') ? Marca::findOrFail($request->marca_id) : null;
        $producto->nombre = $request->nombre;
        $producto->marca = $marca?->nombre ?? '';
        $producto->categoria_nombre = $categoria->nombre;
        $producto->codigo = $request->codigo;
        $producto->descripcion_corta = $request->descripcion_corta;
        $producto->descripcion_larga = $request->descripcion_larga;
        $producto->descripcion = $request->descripcion_corta;
        $producto->detalles_tecnicos = Producto::sanitizeHtml($request->detalles_tecnicos);
        $producto->precio_compra = $request->precio_compra;
        $producto->precio_venta = $request->precio_venta;
        $producto->precio_oferta = $request->precio_oferta;
        $producto->precio_oferta_manual = $request->precio_oferta;
        $producto->descuento = $request->descuento;
        $producto->en_oferta = $request->filled('precio_oferta') && $request->precio_oferta > 0;
        $producto->stock = $request->stock;
        $producto->disponible = (int) $request->stock > 0;
        $producto->categoria_id = $request->categoria_id;
        $producto->marca_id = $request->marca_id;

        $producto->destacado = $request->has('destacado');
        $producto->nuevo = $request->has('nuevo');
        $producto->recomendado = $request->has('recomendado');
        $producto->mas_vendido = $request->has('mas_vendido');

        $producto->save();

        return redirect()->route('admin.productos.index')
            ->with('mensaje', 'Producto actualizado exitosamente')
            ->with('icono', 'success');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $producto = Producto::findOrFail($id);

        $producto->getConnection()->transaction(function () use ($producto): void {
            $this->soltarDelCarritoYFavoritos([$producto->id]);
            $producto->delete();
        });

        return redirect()->route('admin.productos.index')
            ->with('mensaje', 'Producto movido a la papelera. Desde ahí se devuelve al catálogo.')
            ->with('icono', 'success');
    }

    /**
     * Selección múltiple del listado. El borrado es suave porque `productos` ya
     * tiene su columna `deleted_at` y el catálogo compartido con la app Android
     * no tiene ninguna copia detrás: un "eliminar todo" definitivo no se deshace.
     */
    public function masivo(Request $request)
    {
        $datos = $request->validate([
            'accion' => ['required', Rule::in(['eliminar', 'restaurar'])],
            'alcance' => ['required', Rule::in(['seleccion', 'filtro'])],
            'ids' => ['exclude_if:alcance,filtro', 'required', 'array'],
            'ids.*' => ['integer', 'min:1'],
            'buscar' => ['nullable', 'string', 'max:255'],
            'confirmar' => ['nullable', 'string', 'max:20'],
        ], [
            'ids.required' => 'Marca primero la casilla del producto que quieras mover.',
            'accion.required' => 'Elige una acción para la selección.',
        ]);

        if ($datos['accion'] === 'eliminar'
            && mb_strtoupper(trim((string) $request->input('confirmar'))) !== 'ELIMINAR') {
            return back()->withInput()
                ->withErrors(['confirmar' => 'Para borrar productos escribe ELIMINAR, tal cual, en la casilla de confirmación.']);
        }

        $ids = $datos['alcance'] === 'filtro'
            ? $this->busqueda($request->input('buscar'))->pluck('id')
            : collect($datos['ids']);

        if ($ids->isEmpty()) {
            return redirect()->route('admin.productos.index')
                ->with('mensaje', 'No había ningún producto en la selección.')
                ->with('icono', 'warning');
        }

        if ($datos['accion'] === 'restaurar') {
            $afectados = Producto::onlyTrashed()->whereIn('id', $ids)->restore();

            $mensaje = $afectados.' producto(s) devueltos al catálogo.';
        } else {
            $afectados = (new Producto)->getConnection()->transaction(function () use ($ids): int {
                $this->soltarDelCarritoYFavoritos($ids->all());

                return Producto::whereIn('id', $ids)->delete();
            });

            $mensaje = $afectados.' producto(s) pasaron a la papelera; desde ahí se devuelven al catálogo.';
        }

        return redirect()->route('admin.productos.index', [
            'buscar' => $request->input('buscar'),
            'eliminados' => $request->boolean('eliminados') ? 1 : null,
        ])
            ->with('mensaje', $mensaje)
            ->with('icono', 'success');
    }

    /**
     * El carrito y los favoritos leen el producto directo en la vista; si la
     * fila desaparece, "Mi carrito" revienta en vez de mostrar el artículo fuera
     * de stock. Son datos transitorios, no historial: se sueltan.
     */
    private function soltarDelCarritoYFavoritos(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $conexion = (new Producto)->getConnectionName();

        DB::connection($conexion)->table('carrito')->whereIn('producto_id', $ids)->delete();

        if (Schema::connection($conexion)->hasTable('favoritos')) {
            DB::connection($conexion)->table('favoritos')->whereIn('producto_id', $ids)->delete();
        }
    }

    public function detalle_producto($id)
    {
        $ajuste = Ajuste::first();
        $producto = Producto::findOrFail($id);

        // --- 1. Productos Vistos Recientemente (Track and Fetch) ---
        $recentlyViewed = session()->get('recently_viewed', []);

        $vistos_recientemente = collect();
        if (! empty($recentlyViewed)) {
            // Fetch products matching the history, excluding the current one, keeping order
            $vistos_recientemente = Producto::with(['imagenes', 'marca', 'categoria'])
                ->whereIn('id', $recentlyViewed)
                ->where('id', '!=', $producto->id)
                ->where('stock', '>', 0)
                ->get()
                ->sortBy(function ($item) use ($recentlyViewed) {
                    return array_search($item->id, $recentlyViewed);
                })
                ->values();
        }

        // Add current product to recently viewed list (max 10)
        if (($key = array_search($producto->id, $recentlyViewed)) !== false) {
            unset($recentlyViewed[$key]);
        }
        array_unshift($recentlyViewed, $producto->id);
        $recentlyViewed = array_slice($recentlyViewed, 0, 10);
        session()->put('recently_viewed', $recentlyViewed);

        // --- 2. Productos Relacionados ---
        $minPrice = $producto->precio_venta * 0.7;
        $maxPrice = $producto->precio_venta * 1.3;

        $productos_relacionados = Producto::with(['imagenes', 'marca', 'categoria'])
            ->where('id', '!=', $producto->id)
            ->where('stock', '>', 0)
            ->select('*')
            ->selectRaw('(CASE WHEN categoria_id = ? THEN 4 ELSE 0 END) +
                         (CASE WHEN marca_id = ? THEN 3 ELSE 0 END) +
                         (CASE WHEN precio_normal BETWEEN ? AND ? THEN 2 ELSE 0 END) as relevance_score', [
                $producto->categoria_id,
                $producto->marca_id,
                $minPrice,
                $maxPrice,
            ])
            ->orderByDesc('relevance_score')
            ->limit(12)
            ->get();

        // Complete with popular products if less than 8 are found
        if ($productos_relacionados->count() < 8) {
            $needed = 12 - $productos_relacionados->count();
            $excludeIds = $productos_relacionados->pluck('id')->push($producto->id);

            $popularIds = DB::connection('galy')
                ->table('pedido_productos')
                ->join('productos', 'productos.id', '=', 'pedido_productos.producto_id')
                ->whereNotIn('productos.id', $excludeIds)
                ->where('productos.stock', '>', 0)
                ->groupBy('productos.id')
                ->orderByRaw('SUM(pedido_productos.cantidad) DESC')
                ->limit($needed)
                ->pluck('productos.id');
            $populares = Producto::with(['imagenes', 'marca', 'categoria'])
                ->whereIn('id', $popularIds)
                ->get()
                ->sortBy(fn($item) => array_search($item->id, $popularIds->all()))
                ->values();

            $productos_relacionados = $productos_relacionados->concat($populares);
        }

        // --- 3. Los Clientes También Compraron ---
        $orderIds = DB::connection('galy')->table('pedido_productos')
            ->where('producto_id', $producto->id)
            ->pluck('pedido_id');

        $tambien_compraron = collect();
        if ($orderIds->isNotEmpty()) {
            $coOccurringProductIds = DB::connection('galy')->table('pedido_productos')
                ->whereIn('pedido_id', $orderIds)
                ->where('producto_id', '!=', $producto->id)
                ->select('producto_id', DB::raw('COUNT(*) as frequency'))
                ->groupBy('producto_id')
                ->orderByDesc('frequency')
                ->limit(12)
                ->pluck('producto_id');

            if ($coOccurringProductIds->count() >= 4) {
                $tambien_compraron = Producto::with(['imagenes', 'marca', 'categoria'])
                    ->whereIn('id', $coOccurringProductIds)
                    ->where('stock', '>', 0)
                    ->get()
                    ->sortBy(function ($item) use ($coOccurringProductIds) {
                        return array_search($item->id, $coOccurringProductIds->toArray());
                    })
                    ->values();
            }
        }

        // --- 4. Productos Mejor Valorados ---
        $hasModernRatingColumns = Schema::connection('galy')->hasColumn('productos', 'rating_count');
        $ratingCountColumn = $hasModernRatingColumns ? 'rating_count' : 'num_revisiones';
        $ratingAvgColumn = $hasModernRatingColumns ? 'rating_avg' : 'calificacion';

        $mejor_valorados = Producto::with(['imagenes', 'marca', 'categoria'])
            ->where('id', '!=', $producto->id)
            ->where('stock', '>', 0)
            ->where($ratingCountColumn, '>', 0)
            ->orderByDesc($ratingAvgColumn)
            ->orderByDesc($ratingCountColumn)
            ->limit(12)
            ->get();

        // --- 5. Productos en Oferta ---
        $productos_oferta = Producto::with(['imagenes', 'marca', 'categoria'])
            ->where('id', '!=', $producto->id)
            ->where('stock', '>', 0)
            ->where('en_oferta', true)
            ->limit(12)
            ->get();

        // --- 6. Más Productos de la Marca ---
        $mas_de_marca = collect();
        if ($producto->marca_id) {
            $mas_de_marca = Producto::with(['imagenes', 'marca', 'categoria'])
                ->where('marca_id', $producto->marca_id)
                ->where('id', '!=', $producto->id)
                ->where('stock', '>', 0)
                ->limit(12)
                ->get();
        }

        // --- 7. El asesor que atiende este rubro ---
        // El rubro del asesor es texto libre, así que coincidir con la categoría del
        // producto es un empujón: si nadie coincide, se muestran los dos primeros
        // visibles y la franja sigue teniendo sentido.
        $categoria = mb_strtolower(trim((string) $producto->categoria?->nombre));
        $asesores_activos = Asesor::activos()->get();
        $asesores_producto = $categoria === ''
            ? collect()
            : $asesores_activos->filter(function (Asesor $asesor) use ($categoria) {
                return str_contains(mb_strtolower(trim((string) $asesor->rubro)), $categoria);
            })->take(2);

        if ($asesores_producto->isEmpty()) {
            $asesores_producto = $asesores_activos->take(2);
        }

        return view('web.detalle_producto', compact(
            'ajuste',
            'producto',
            'productos_relacionados',
            'tambien_compraron',
            'mejor_valorados',
            'productos_oferta',
            'mas_de_marca',
            'vistos_recientemente',
            'asesores_producto'
        ));
    }
}
