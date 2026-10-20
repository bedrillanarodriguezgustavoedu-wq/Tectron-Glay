<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\Carrito;
use App\Models\ProductoFavorito;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProductoFavoritoController extends Controller
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
        $productos_favoritos = ProductoFavorito::where('usuario_id', Auth::user()->galy_user_id)
            ->with('producto.imagenes')
            ->get();

        return view('web.favoritos', compact('productos_favoritos', 'ajuste'));
    }

    public function toggle(Request $request)
    {
        if (Auth::guest()) {
            return response()->json([
                'success' => false,
                'message' => 'Inicia sesión para gestionar tus productos favoritos.',
                'code' => 401,
            ], 401);
        }

        $request->validate([
            'producto_id' => ['required', Rule::exists('galy.productos', 'id')],
        ]);

        $userId = Auth::user()->galy_user_id;
        $fav = ProductoFavorito::where('usuario_id', $userId)
            ->where('producto_id', $request->producto_id)
            ->first();

        if ($fav) {
            $fav->delete();
            $action = 'removed';
            $message = 'Producto removido de favoritos.';
        } else {
            ProductoFavorito::create([
                'usuario_id' => $userId,
                'producto_id' => $request->producto_id,
            ]);
            $action = 'added';
            $message = 'Producto agregado a favoritos.';
        }

        $contador = ProductoFavorito::where('usuario_id', $userId)->count();

        return response()->json([
            'success' => true,
            'action' => $action,
            'mensaje' => $message,
            'contador' => $contador,
        ]);
    }

    public function agregarTodoAlCarrito(Request $request)
    {
        if (Auth::guest()) {
            return response()->json([
                'success' => false,
                'message' => 'Debes iniciar sesión para realizar esta acción.',
                'code' => 401,
            ], 401);
        }

        $userId = Auth::user()->galy_user_id;
        $favoritos = ProductoFavorito::where('usuario_id', $userId)
            ->with('producto')
            ->get();

        if ($favoritos->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes productos en favoritos para agregar.',
            ]);
        }

        $agregados = 0;
        foreach ($favoritos as $fav) {
            $prod = $fav->producto;
            if ($prod && $prod->stock > 0) {
                $cart_exist = Carrito::where('usuario_id', $userId)
                    ->where('producto_id', $prod->id)
                    ->first();

                if (! $cart_exist) {
                    Carrito::create([
                        'usuario_id' => $userId,
                        'producto_id' => $prod->id,
                        'cantidad' => 1,
                    ]);
                    $agregados++;
                }

                $fav->delete();
            }
        }

        $cart_count = Carrito::where('usuario_id', $userId)->sum('cantidad');
        $fav_count = ProductoFavorito::where('usuario_id', $userId)->count();

        return response()->json([
            'success' => true,
            'mensaje' => "Se agregaron {$agregados} productos nuevos al carrito.",
            'cart_count' => $cart_count,
            'fav_count' => $fav_count,
        ]);
    }
}
