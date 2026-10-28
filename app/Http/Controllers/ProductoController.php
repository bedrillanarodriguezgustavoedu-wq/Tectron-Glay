<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\Asesor;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\ProductoImagen;
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
        $query = Producto::query()->with('categoria');
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

        if ($request->filled('buscar')) {
            $search = $request->string('buscar')->toString();
            $query->where(function ($productQuery) use ($search) {
                $productQuery
                    ->where('nombre', 'like', "%{$search}%")
                    ->orWhere('codigo', 'like', "%{$search}%")
                    ->orWhere('descripcion_corta', 'like', "%{$search}%")
                    ->orWhere('descripcion_larga', 'like', "%{$search}%");
            });
        }

        $productos = $query->paginate(10)->withQueryString();
        $productosGaly = Producto::count();
        $productosTectron = $hasProductMap
            ? DB::table('tectron_galy_product_map')->count()
            : 0;

        return view('admin.productos.index', compact(
            'productos',
            'ajuste',
            'productosGaly',
            'productosTectron',
            'hasProductMap'
        ));
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

        return redirect()->route('admin.productos.imagenes', $producto->id)
            ->with('mensaje', 'Imagen subida exitosamente')
            ->with('icono', 'success');
    }

    public function destroy_imagen(Request $request, $id)
    {
        $imagenProducto = ProductoImagen::findOrFail($id);
        $productoId = $imagenProducto->producto_id;
        $imagePath = basename((string) $imagenProducto->imagen);
        $imageProduct = Producto::findOrFail($productoId);
        $wasPrimary = $imageProduct->imagen_url === $imagePath;

        DB::connection('galy')->transaction(function () use ($imagenProducto, $imageProduct, $productoId, $wasPrimary) {
            $imagenProducto->delete();

            if ($wasPrimary) {
                $nextImage = ProductoImagen::where('producto_id', $productoId)->first();
                $imageProduct->setAttribute('imagen_url', $nextImage?->imagen);
                $imageProduct->save();
            }
        });

        return redirect()->route('admin.productos.imagenes', $productoId)
            ->with('mensaje', 'Imagen eliminada exitosamente')
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
        DB::connection('galy')->transaction(function () use ($producto) {
            $producto->imagenes()->delete();
            $producto->delete();
        });

        return redirect()->route('admin.productos.index')
            ->with('mensaje', 'Producto eliminado exitosamente')
            ->with('icono', 'success');
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
