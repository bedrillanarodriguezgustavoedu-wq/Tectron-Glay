<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\Carrito;
use App\Models\Direccion;
use App\Models\Pais;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        return view('web.carritos', compact('carritos', 'ajuste', 'paises', 'direcciones'));
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

                return response()->json([
                    'success' => true,
                    'mensaje' => 'Carrito actualizado correctamente.',
                    'contador' => $contador,
                    'items' => $items_data,
                    'subtotal_original_acumulado' => $subtotal_original_acumulado,
                    'subtotal_original_acumulado_formateado' => Ajuste::formatear_precio($subtotal_original_acumulado),
                    'subtotal_acumulado' => $subtotal_acumulado,
                    'subtotal_acumulado_formateado' => Ajuste::formatear_precio($subtotal_acumulado),
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

    private function unlinkedAccountResponse(Request $request)
    {
        $message = 'No se encontró una cuenta GALY vinculada a este correo. Inicia sesión con tu cuenta GALY para compartir el carrito.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $message], 409);
        }

        return redirect()->back()->with('mensaje', $message)->with('icono', 'warning');
    }
}
