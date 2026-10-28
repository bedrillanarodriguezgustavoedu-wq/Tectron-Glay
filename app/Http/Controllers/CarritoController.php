<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\Carrito;
use App\Models\Cupon;
use App\Models\Direccion;
use App\Models\Pais;
use App\Models\Producto;
use App\Models\PuntoRecojo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class CarritoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (Auth::guest()) {
            return redirect()->route('web.login')
                ->with('mensaje', 'Debes iniciar sesión para ver tus productos favoritos')
                ->with('icono', 'warning');
        }

        $ajuste = Ajuste::first();
        $userId = Auth::user()->resolveGalyUserId();
        $carritos = $userId
            ? Carrito::where('usuario_id', $userId)->with('producto.imagenes')->get()
            : collect();

        $paises = Pais::where('activo', true)->where('realiza_envios', true)->orderBy('nombre', 'asc')->get();
        $direcciones = Direccion::where('usuario_id', Auth::user()->id)
            ->with(['pais', 'departamento', 'provincia'])
            ->get();

        // Blindado porque el carrito es la página más cara del flujo: si la
        // migración de puntos de recojo aún no está aplicada, el cliente sigue
        // pudiendo comprar con la dirección general de Ajustes.
        $puntosRecojo = Schema::hasTable('puntos_recojo')
            ? PuntoRecojo::activos()->deTienda()->get()
            : collect();

        $agencias = Schema::hasTable('puntos_recojo')
            ? PuntoRecojo::agenciasPorProvincia()
            : collect();

        // El código vive en la sesión, nunca el monto: se resuelve contra el
        // subtotal real en cada visita para que un cupón vencido deje de aplicar
        // solo con recargar la página.
        $subtotalCarrito = $this->subtotalCarrito((int) $userId);
        $codigoSesion = (string) session('carrito_cupon', '');
        $cupon = $codigoSesion === ''
            ? ['codigo' => null, 'porcentaje' => 0, 'descuento' => 0.0, 'mensaje' => null]
            : Cupon::aplicar($codigoSesion, $subtotalCarrito);

        if ($codigoSesion !== '' && $cupon['codigo'] === null) {
            session()->forget('carrito_cupon');
        }

        return view('web.carritos', compact(
            'carritos',
            'ajuste',
            'paises',
            'direcciones',
            'puntosRecojo',
            'agencias',
            'cupon',
            'subtotalCarrito'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (Auth::guest()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debes iniciar sesión para agregar productos al carrito.',
                    'code' => 401,
                ], 401);
            }

            return redirect()->route('web.login')
                ->with('mensaje', 'Debes iniciar sesión para agregar productos a su carrito')
                ->with('icono', 'warning');
        }
        $request->validate([
            'producto_id' => ['required', Rule::exists('galy.productos', 'id')],
            'cantidad' => 'required|integer|min:1',
        ]);

        $userId = Auth::user()->resolveGalyUserId();
        if ($userId === null) {
            return $this->unlinkedAccountResponse($request);
        }

        $result = DB::connection('galy')->transaction(function () use ($request, $userId): array {
            $producto = Producto::whereKey($request->producto_id)->lockForUpdate()->firstOrFail();
            $carrito = Carrito::where('usuario_id', $userId)
                ->where('producto_id', $producto->id)
                ->lockForUpdate()
                ->first();
            $cantidadNueva = $request->integer('cantidad') + ($carrito?->cantidad ?? 0);

            if ($producto->stock < $cantidadNueva) {
                return [
                    'error' => "No hay suficiente stock disponible. Stock actual: {$producto->stock}",
                ];
            }

            if ($carrito) {
                $carrito->cantidad = $cantidadNueva;
                $carrito->save();
            } else {
                Carrito::create([
                    'usuario_id' => $userId,
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidadNueva,
                ]);
            }

            return ['success' => true];
        });

        if (isset($result['error'])) {
            $msg = $result['error'];
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 400);
            }

            return redirect()->back()->with('mensaje', $msg)->with('icono', 'error');
        }

        $contador = Carrito::where('usuario_id', $userId)->sum('cantidad');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'mensaje' => 'Producto agregado al carrito correctamente.',
                'contador' => $contador,
            ]);
        }

        return redirect()->route('web.carrito.index')
            ->with('mensaje', 'Producto agregado al carrito correctamente')
            ->with('icono', 'success');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        if (Auth::guest()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debes iniciar sesión para realizar esta acción.',
                    'code' => 401,
                ], 401);
            }

            return redirect()->route('web.login');
        }

        $userId = Auth::user()->resolveGalyUserId();
        if ($userId === null) {
            return $this->unlinkedAccountResponse($request);
        }

        // Support bulk update
        if ($request->has('cantidades')) {
            $request->validate([
                'cantidades' => 'required|array',
                'cantidades.*' => 'required|integer|min:1',
            ]);

            $errores_stock = [];

            foreach ($request->cantidades as $carrito_id => $cantidad) {
                $carrito = Carrito::where('usuario_id', $userId)->find($carrito_id);
                if ($carrito && $carrito->producto) {
                    if ($carrito->producto->stock < $cantidad) {
                        $errores_stock[] = "El producto '{$carrito->producto->nombre}' solo tiene {$carrito->producto->stock} unidades disponibles.";
                    } else {
                        $carrito->cantidad = $cantidad;
                        $carrito->save();
                    }
                }
            }

            if ($request->expectsJson() || $request->ajax()) {
                if (! empty($errores_stock)) {
                    return response()->json([
                        'success' => false,
                        'mensaje' => implode(' ', $errores_stock),
                    ], 400);
                }

                $carritos = Carrito::where('usuario_id', $userId)
                    ->with('producto')
                    ->get();

                $subtotal_original_acumulado = 0;
                $subtotal_acumulado = 0;
                $items_data = [];

                foreach ($carritos as $c) {
                    $precio_original = $c->producto->precio_venta;
                    $precio_actual = ($c->producto->en_oferta && $c->producto->precio_oferta > 0)
                        ? $c->producto->precio_oferta
                        : $c->producto->precio_venta;

                    $subtotal_original_item = $precio_original * $c->cantidad;
                    $subtotal_item = $precio_actual * $c->cantidad;
                    $ahorro_item = $subtotal_original_item - $subtotal_item;

                    $subtotal_original_acumulado += $subtotal_original_item;
                    $subtotal_acumulado += $subtotal_item;

                    $items_data[] = [
                        'id' => $c->id,
                        'cantidad' => $c->cantidad,
                        'subtotal_item' => $subtotal_item,
                        'subtotal_item_formateado' => Ajuste::formatear_precio($subtotal_item),
                        'ahorro_item' => $ahorro_item,
                        'ahorro_item_formateado' => Ajuste::formatear_precio($ahorro_item),
                    ];
                }

                $contador = Carrito::where('usuario_id', $userId)->sum('cantidad');

                // El carrito cambió: el cupón se resuelve contra el subtotal nuevo
                // para que el resumen mostrado sea el mismo que cobrará el pedido.
                $codigoSesion = (string) $request->session()->get('carrito_cupon', '');
                $cupon = $codigoSesion === ''
                    ? ['codigo' => null, 'porcentaje' => 0, 'descuento' => 0.0, 'mensaje' => null]
                    : Cupon::aplicar($codigoSesion, $subtotal_acumulado);
                if ($codigoSesion !== '' && $cupon['codigo'] === null) {
                    $request->session()->forget('carrito_cupon');
                }

                return response()->json([
                    'success' => true,
                    'mensaje' => 'Carrito actualizado correctamente.',
                    'contador' => $contador,
                    'items' => $items_data,
                    'subtotal_original_acumulado' => $subtotal_original_acumulado,
                    'subtotal_original_acumulado_formateado' => Ajuste::formatear_precio($subtotal_original_acumulado),
                    'subtotal_acumulado' => $subtotal_acumulado,
                    'subtotal_acumulado_formateado' => Ajuste::formatear_precio($subtotal_acumulado),
                    'cupon' => [
                        'codigo' => $cupon['codigo'],
                        'porcentaje' => $cupon['porcentaje'],
                        'descuento' => $cupon['descuento'],
                    ],
                ]);
            }

            if (! empty($errores_stock)) {
                return redirect()->route('web.carrito.index')
                    ->with('mensaje', 'Se actualizaron algunos productos, pero: '.implode(' ', $errores_stock))
                    ->with('icono', 'warning');
            }

            return redirect()->route('web.carrito.index')
                ->with('mensaje', 'Carrito actualizado correctamente')
                ->with('icono', 'success');
        }

        // Support single update (fallback)
        $request->validate([
            'carrito_id' => 'required|exists:carrito,id',
            'cantidad' => 'required|integer|min:1',
        ]);

        $carrito = Carrito::where('usuario_id', $userId)->findOrFail($request->carrito_id);
        if ($carrito->producto->stock < $request->cantidad) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'mensaje' => "No hay suficiente stock. Disponible: {$carrito->producto->stock}",
                ], 400);
            }

            return redirect()->back()
                ->with('mensaje', "No hay suficiente stock. Disponible: {$carrito->producto->stock}")
                ->with('icono', 'error');
        }

        $carrito->cantidad = $request->cantidad;
        $carrito->save();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'mensaje' => 'Cantidad actualizada correctamente',
            ]);
        }

        return redirect()->route('web.carrito.index')
            ->with('mensaje', 'Cantidad actualizada correctamente')->with('icono', 'success');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $request = request();
        $userId = Auth::user()->resolveGalyUserId();
        if ($userId === null) {
            return $this->unlinkedAccountResponse($request);
        }

        $carrito = Carrito::where('usuario_id', $userId)->findOrFail($id);
        $carrito->delete();

        return redirect()->route('web.carrito.index')
            ->with('mensaje', 'Producto eliminado del carrito correctamente')->with('icono', 'success');
    }

    public function limpiar(Request $request)
    {
        $userId = Auth::user()->resolveGalyUserId();
        if ($userId === null) {
            return $this->unlinkedAccountResponse($request);
        }

        Carrito::where('usuario_id', $userId)->delete();

        return redirect()->route('web.carrito.index')
            ->with('mensaje', 'Carrito limpiado correctamente')->with('icono', 'success');
    }

    /**
     * Aplica o quita el cupón. Aquí solo se guarda el CÓDIGO en la sesión: el
     * descuento se recalcula contra el subtotal real en cada visita y otra vez al
     * crear el pedido, para que un código inyectado por POST no pueda cambiar un monto.
     */
    public function cupon(Request $request)
    {
        $request->validate([
            'codigo' => 'nullable|string|max:30',
            'quitar' => 'nullable|boolean',
        ]);

        $codigo = $request->boolean('quitar') ? '' : trim((string) $request->input('codigo'));

        if ($codigo === '') {
            $request->session()->forget('carrito_cupon');

            return redirect()->route('web.carrito.index')
                ->with('mensaje', 'Quitamos el cupón: el carrito vuelve a su precio normal.')
                ->with('icono', 'success');
        }

        $userId = Auth::user()->resolveGalyUserId();
        if ($userId === null) {
            return $this->unlinkedAccountResponse($request);
        }

        $subtotal = $this->subtotalCarrito($userId);
        if ($subtotal <= 0.0) {
            $request->session()->forget('carrito_cupon');

            return redirect()->route('web.carrito.index')
                ->withInput(['codigo_cupon' => $codigo])
                ->with('mensaje', 'Agrega productos al carrito antes de usar un cupón.')
                ->with('icono', 'warning');
        }

        $resuelto = Cupon::aplicar($codigo, $subtotal);

        if ($resuelto['mensaje'] !== null) {
            $request->session()->forget('carrito_cupon');

            return redirect()->route('web.carrito.index')
                ->withInput(['codigo_cupon' => $codigo])
                ->with('mensaje', $resuelto['mensaje'])
                ->with('icono', 'warning');
        }

        $request->session()->put('carrito_cupon', $resuelto['codigo']);

        return redirect()->route('web.carrito.index')
            ->with('mensaje', 'Cupón '.$resuelto['codigo'].' aplicado: '.
                Ajuste::formatear_precio($resuelto['descuento']).' de descuento ('.$resuelto['porcentaje'].'%).')
            ->with('icono', 'success');
    }

    /**
     * Mismo criterio de precio que usan la vista del carrito y `OrdenController`:
     * oferta vigente o precio de venta.
     */
    private function subtotalCarrito(int $userId): float
    {
        $subtotal = 0.0;

        foreach (Carrito::where('usuario_id', $userId)->with('producto')->get() as $item) {
            if (! $item->producto) {
                continue;
            }

            $precio = $item->producto->en_oferta && $item->producto->precio_oferta > 0
                ? $item->producto->precio_oferta
                : $item->producto->precio_venta;

            $subtotal += (float) $precio * (int) $item->cantidad;
        }

        return round($subtotal, 2);
    }

    private function unlinkedAccountResponse(Request $request)
    {
        $message = 'No se encontró una cuenta GALY vinculada a este correo. Inicia sesión con tu cuenta GALY para compartir el carrito.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $message], 409);
        }

        return redirect()->back()->with('mensaje', $message)->with('icono', 'warning');
    }
}
