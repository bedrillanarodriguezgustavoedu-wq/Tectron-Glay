<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\Asesor;
use App\Models\Banner;
use App\Models\Carrusel;
use App\Models\Categoria;
use App\Models\Contacto;
use App\Models\FooterSettings;
use App\Models\Marca;
use App\Models\MensajeContacto;
use App\Models\Nosotros;
use App\Models\Oferta;
use App\Models\Producto;
use App\Models\SeccionHome;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WebController extends Controller
{
    public function index()
    {
        $ajuste = Ajuste::first();

        // Sync offers dynamically
        if (class_exists('\App\Models\Oferta')) {
            Oferta::refreshProductPrices();
        }

        // 1. Slider Banners (Max 5, ordered, active)
        $carruseles = Carrusel::where('activo', true)
            ->orderBy('orden', 'asc')
            ->limit(5)
            ->get();

        // Banners
        $banners = Banner::where('activo', true)
            ->where(function ($query) {
                $query->whereNull('fecha_publicacion')
                    ->orWhere('fecha_publicacion', '<=', now());
            })
            ->whereNotNull('imagen')
            ->orderBy('orden', 'asc')
            ->get();

        // 2. Countdown Offer detection (Active vs Programmed)
        $oferta_activa = null;
        $oferta_programada = null;

        if ($ajuste && $ajuste->home_mostrar_contador) {
            // Check if there is a configured featured offer
            if ($ajuste->home_destacado_oferta_id) {
                $featured = Oferta::where('id', $ajuste->home_destacado_oferta_id)
                    ->where('activo', true)
                    ->first();
                if ($featured) {
                    if ($featured->fecha_inicio <= now() && $featured->fecha_fin > now()) {
                        $oferta_activa = $featured;
                    } elseif ($featured->fecha_inicio > now()) {
                        $oferta_programada = $featured;
                    }
                }
            }

            // If not found or not active/programmed, auto-detect the next active or future offer
            if (! $oferta_activa && ! $oferta_programada) {
                $oferta_activa = Oferta::where('activo', true)
                    ->where('fecha_inicio', '<=', now())
                    ->where('fecha_fin', '>', now())
                    ->orderBy('fecha_fin', 'asc')
                    ->first();

                if (! $oferta_activa) {
                    $oferta_programada = Oferta::where('activo', true)
                        ->where('fecha_inicio', '>', now())
                        ->orderBy('fecha_inicio', 'asc')
                        ->first();
                }
            }
        }

        // 3. Categories priorities and products eager loading
        $max_cats = $ajuste->home_max_categorias ?? 3;
        $max_prods = $ajuste->home_max_productos_categoria ?? 15;

        // Categories sorted by highest sales count in detail orders
        $top_categorias_ids = DB::connection('galy')->table('pedido_productos')
            ->join('productos', 'pedido_productos.producto_id', '=', 'productos.id')
            ->select('productos.categoria_id', DB::raw('SUM(pedido_productos.cantidad) as total_ventas'))
            ->groupBy('productos.categoria_id')
            ->orderByDesc('total_ventas')
            ->pluck('productos.categoria_id')
            ->toArray();

        $categorias_list = Categoria::whereIn('id', $top_categorias_ids)->get();

        // Sort collection matching top sales order
        $categorias_list = $categorias_list->sortBy(function ($cat) use ($top_categorias_ids) {
            return array_search($cat->id, $top_categorias_ids);
        });

        // Fill up to max categories using active categories as fallback
        if ($categorias_list->count() < $max_cats) {
            $remaining_cats = Categoria::whereNotIn('id', $categorias_list->pluck('id'))
                ->limit($max_cats - $categorias_list->count())
                ->get();
            $categorias_list = $categorias_list->concat($remaining_cats);
        }

        $categorias_home = $categorias_list->take($max_cats);

        // Load products per category with Eager Loading
        foreach ($categorias_home as $cat) {
            $cat->setRelation('productos_carrusel', $cat->productos()
                ->with(['imagenes', 'marca', 'categoria'])
                ->limit($max_prods)
                ->get()
            );
        }

        // 4. Secciones Home (Banners, Marcas, etc. customizable structure)
        $secciones = SeccionHome::where('activo', true)->orderBy('orden', 'asc')->get();

        $datos_secciones = [];
        foreach ($secciones as $seccion) {
            switch ($seccion->clave) {
                case 'productos_destacados':
                    $datos_secciones['productos_destacados'] = Producto::with(['imagenes', 'marca', 'categoria'])
                        ->where('destacado', true)
                        ->limit($seccion->limite)
                        ->get();
                    break;
                case 'marcas':
                    $datos_secciones['marcas'] = Marca::where('activo', true)
                        ->withCount('productos')
                        ->limit($seccion->limite)
                        ->get();
                    break;
                case 'ofertas':
                    $datos_secciones['ofertas'] = Producto::with(['imagenes', 'marca', 'categoria'])
                        ->where('en_oferta', true)
                        ->limit($seccion->limite)
                        ->get();
                    break;
                case 'nuevos_productos':
                    $datos_secciones['nuevos_productos'] = Producto::with(['imagenes', 'marca', 'categoria'])
                        ->where('nuevo', true)
                        ->limit($seccion->limite)
                        ->get();
                    break;
                case 'productos_recomendados':
                    $datos_secciones['productos_recomendados'] = Producto::with(['imagenes', 'marca', 'categoria'])
                        ->where('recomendado', true)
                        ->limit($seccion->limite)
                        ->get();
                    break;
                case 'categorias_populares':
                    $datos_secciones['categorias_populares'] = Categoria::withCount('productos')
                        ->orderByDesc('productos_count')
                        ->limit($seccion->limite)
                        ->get();
                    break;
            }
        }

        return view('web.index', compact('ajuste', 'carruseles', 'banners', 'secciones', 'datos_secciones', 'oferta_activa', 'oferta_programada', 'categorias_home'));
    }

    private function getFilterSidebarData()
    {
        $productConnection = (new Producto)->getConnectionName();
        $priceColumn = Schema::connection($productConnection)->hasColumn('productos', 'precio_normal')
            ? 'precio_normal'
            : 'precio_venta';

        return [
            'categorias_sidebar' => Categoria::withCount('productos')->get(),
            'marcas_sidebar' => Marca::where('activo', true)->withCount('productos')->get(),
            'max_precio_limit' => ceil(Producto::max($priceColumn) ?? 5000),
        ];
    }

    public function buscar_producto(Request $request)
    {
        $ajuste = Ajuste::first();
        $query = $request->input('producto') ?: $request->input('buscar');

        $productos = Producto::with(['imagenes', 'categoria', 'marca'])
            ->filtrar($request)
            ->paginate(12)
            ->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('partials.productos_grid', compact('productos'))->render(),
                'total' => $productos->total(),
            ]);
        }

        $sidebarData = $this->getFilterSidebarData();

        return view('web.buscar', array_merge(compact('ajuste', 'productos', 'query'), $sidebarData));
    }

    public function productos(Request $request)
    {
        $ajuste = Ajuste::first();

        $productos = Producto::with(['imagenes', 'categoria', 'marca'])
            ->filtrar($request)
            ->paginate(12)
            ->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('partials.productos_grid', compact('productos'))->render(),
                'total' => $productos->total(),
            ]);
        }

        $sidebarData = $this->getFilterSidebarData();

        return view('web.productos', array_merge(compact('ajuste', 'productos'), $sidebarData));
    }

    public function ofertas(Request $request)
    {
        $ajuste = Ajuste::first();

        // Force state filter to 'en_oferta'
        $request->merge(['estado' => array_merge((array) $request->input('estado'), ['en_oferta'])]);

        $productos = Producto::with(['imagenes', 'categoria', 'marca'])
            ->filtrar($request)
            ->paginate(12)
            ->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('partials.productos_grid', compact('productos'))->render(),
                'total' => $productos->total(),
            ]);
        }

        $sidebarData = $this->getFilterSidebarData();

        return view('web.ofertas', array_merge(compact('ajuste', 'productos'), $sidebarData));
    }

    public function marcas()
    {
        $ajuste = Ajuste::first();
        $marcas = Marca::where('activo', true)->withCount('productos')->get();

        return view('web.marcas', compact('ajuste', 'marcas'));
    }

    public function nosotros()
    {
        $ajuste = Ajuste::first();
        $nosotros = Nosotros::first();
        if (! $nosotros || ! $nosotros->activo) {
            abort(404, 'La página sobre nosotros no está activa.');
        }

        return view('web.nosotros', compact('ajuste', 'nosotros'));
    }

    public function asesores()
    {
        $ajuste = Ajuste::first();
        $asesores = Asesor::activos()->get();

        // El filtro por rubro se arma con lo que realmente tiene asesores: un
        // chip que no devuelve ninguna tarjeta solo confunde al cliente.
        $rubros = $asesores
            ->map(fn (Asesor $asesor) => trim((string) $asesor->rubro))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('web.asesores', compact('ajuste', 'asesores', 'rubros'));
    }

    public function contacto()
    {
        $ajuste = Ajuste::first();
        $contacto = Contacto::first();
        if (! $contacto || ! $contacto->activo) {
            abort(404, 'La página de contacto no está activa.');
        }

        return view('web.contacto', compact('ajuste', 'contacto'));
    }

    public function guardar_mensaje(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'asunto' => 'required|string|max:255',
            'mensaje' => 'required|string',
        ]);

        MensajeContacto::create([
            'nombre' => $request->nombre,
            'email' => $request->email,
            'asunto' => $request->asunto,
            'mensaje' => $request->mensaje,
            'estado' => 'Pendiente',
        ]);

        return redirect()->back()
            ->with('mensaje', '¡Mensaje enviado con éxito! Nos pondremos en contacto contigo pronto.')
            ->with('icono', 'success');
    }

    public function buscar_sugerencias(Request $request)
    {
        $query = $request->input('q');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $productos = Producto::with(['imagenes', 'categoria', 'marca'])
            ->where(function ($q) use ($query) {
                $q->where('nombre', 'LIKE', "%$query%")
                    ->orWhere('descripcion_corta', 'LIKE', "%$query%")
                    ->orWhereHas('categoria', function ($c) use ($query) {
                        $c->where('nombre', 'LIKE', "%$query%");
                    })
                    ->orWhereHas('marca', function ($m) use ($query) {
                        $m->where('nombre', 'LIKE', "%$query%");
                    });
            })
            ->limit(6)
            ->get();

        $resultados = $productos->map(function ($prod) {
            $img = $prod->imagenes->first();

            return [
                'id' => $prod->id,
                'nombre' => $prod->nombre,
                'precio_venta' => $prod->precio_venta,
                'precio_oferta' => $prod->precio_oferta,
                'descuento' => $prod->descuento,
                'categoria' => $prod->categoria->nombre ?? '',
                'marca' => $prod->marca->nombre ?? '',
                'imagen_url' => $img ? asset('storage/'.$img->imagen) : \App\Models\ProductoImagen::resolveUrl(null),
                'url' => route('web.detalle_producto', $prod->id),
            ];
        });

        return response()->json($resultados);
    }

    public function categoria(Categoria $categoria, Request $request)
    {
        $ajuste = Ajuste::first();

        // Force category filter
        $request->merge(['categoria' => [$categoria->slug]]);

        $productos = Producto::with(['imagenes', 'marca', 'categoria'])
            ->filtrar($request)
            ->paginate(12)
            ->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('partials.productos_grid', compact('productos'))->render(),
                'total' => $productos->total(),
            ]);
        }

        $sidebarData = $this->getFilterSidebarData();

        return view('web.productos', array_merge(compact('ajuste', 'productos', 'categoria'), $sidebarData));
    }

    public function terminos()
    {
        $ajuste = Ajuste::first();
        $footer_settings = FooterSettings::first();
        $contenido = $footer_settings->terminos_condiciones_contenido ?? '<h1>Términos y Condiciones</h1><p>Contenido por defecto.</p>';

        return view('web.legal', compact('ajuste', 'contenido'));
    }

    public function privacidad()
    {
        $ajuste = Ajuste::first();
        $footer_settings = FooterSettings::first();
        $contenido = $footer_settings?->politica_privacidad_contenido;
        if (! filled($contenido) || str_contains(mb_strtolower($contenido), 'contenido por defecto')) {
            $nombreTienda = e($ajuste?->nombre ?? config('app.name'));
            $correoTienda = $ajuste?->email ? e($ajuste->email) : null;
            $telefonoTienda = $ajuste?->telefono ? e($ajuste->telefono) : null;
            $contactos = [];
            if ($correoTienda) {
                $contactos[] = "<a href=\"mailto:{$correoTienda}\">{$correoTienda}</a>";
            }
            if ($telefonoTienda) {
                $telefonoEnlace = e(preg_replace('/[^0-9+]/', '', (string) $ajuste->telefono));
                $contactos[] = "<a href=\"tel:{$telefonoEnlace}\">{$telefonoTienda}</a>";
            }
            $contacto = $contactos
                ? implode(' · ', $contactos)
                : 'los canales de contacto publicados en esta tienda';

            $contenido = <<<HTML
<h1>Política de Privacidad</h1>
<p><strong>{$nombreTienda}</strong> utiliza los datos que proporcionas para gestionar tu cuenta, preparar tus pedidos y coordinar su entrega.</p>
<h2>Datos que utilizamos</h2>
<ul>
    <li>Nombre y correo electrónico asociado a tu cuenta. Si ingresas con Google, Google confirma la identidad del correo; esta tienda no recibe tu contraseña de Google.</li>
    <li>DNI, que se almacena cifrado en la base de datos de esta aplicación.</li>
    <li>Teléfono y dirección registrada, incluso cuando solicitas recojo en tienda, para identificar el pedido y facilitar la coordinación.</li>
    <li>Información del pedido y, cuando corresponde, el comprobante de pago enviado para revisión manual.</li>
</ul>
<h2>Uso y acceso</h2>
<p>Los datos se usan para registrar y atender la compra, contactarte por el pedido y cumplir las obligaciones aplicables. El acceso administrativo se limita a la gestión de la tienda. Los pagos Yape y Plin son revisados manualmente; no se confirma una transferencia automáticamente.</p>
<h2>Conservación y solicitudes</h2>
<p>El DNI y la dirección se conservan mientras tu cuenta esté activa y, después, durante el plazo exigido por las obligaciones legales aplicables. Cuando dejen de ser necesarios y no exista una obligación legal de conservarlos, se eliminarán o anonimizarán. Para consultar, corregir o solicitar información sobre tus datos, comunícate con {$contacto}.</p>
<p>Al continuar con el checkout, confirmas que leíste esta política y aceptas el uso de los datos descrito para gestionar tu pedido.</p>
HTML;
        }

        return view('web.legal', compact('ajuste', 'contenido'));
    }

    public function cookies()
    {
        $ajuste = Ajuste::first();
        $footer_settings = FooterSettings::first();
        $contenido = $footer_settings->politica_cookies_contenido ?? '<h1>Política de Cookies</h1><p>Contenido por defecto.</p>';

        return view('web.legal', compact('ajuste', 'contenido'));
    }

    public function cambios_devoluciones()
    {
        $ajuste = Ajuste::first();
        $footer_settings = FooterSettings::first();
        $contenido = $footer_settings->cambios_devoluciones_contenido ?? '<h1>Cambios y Devoluciones</h1><p>Contenido por defecto.</p>';

        return view('web.legal', compact('ajuste', 'contenido'));
    }
}
