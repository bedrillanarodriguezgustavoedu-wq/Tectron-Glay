<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\LoginController;
use App\Models\Ajuste;
use App\Models\Direccion;
use App\Models\Orden;
use App\Models\Pais;
use App\Services\Galy\GalyAccountBridge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DashboardController extends Controller
{
    public function index()
    {
        if (Auth::check()) {
            $ajuste = Ajuste::first();
            $galyUserId = Auth::user()->galy_user_id;
            $pedidosQuery = Orden::query();

            if ($galyUserId === null) {
                $pedidosQuery->whereRaw('1 = 0');
                $total_pedidos = 0;
            } else {
                $pedidosQuery->where('usuario_id', $galyUserId);
                $total_pedidos = (clone $pedidosQuery)->count();
            }

            $pedidos = $pedidosQuery
                ->with(['usuario', 'detalles.producto.imagenes', 'historial'])
                // Los pedidos de la app no tienen `created_at`: ordenar por esa
                // columna los manda siempre al final sin importar su fecha real.
                ->orderByDesc(DB::raw('COALESCE(created_at, fecha)'))
                ->paginate(10)
                ->withQueryString();

            return view('web.dashboard', compact('ajuste', 'total_pedidos', 'pedidos'));
        } else {
            return redirect('/web/login');
        }
    }

    public function login(Request $request)
    {
        $ajuste = Ajuste::first();
        $galyLoginMode = in_array($request->query('galy'), ['connect', 'renew'], true)
            ? $request->query('galy')
            : null;

        return view('web.login', compact('ajuste', 'galyLoginMode'));
    }

    public function ajustes()
    {
        $ajuste = Ajuste::first();
        $paises = Pais::where('activo', true)
            ->where('realiza_envios', true)
            ->orderBy('nombre')
            ->get();
        $direcciones = Auth::check()
            ? Direccion::where('usuario_id', Auth::user()->id)->with(['pais', 'departamento', 'provincia'])->get()
            : collect();

        return view('web.ajustes', compact('ajuste', 'paises', 'direcciones'));
    }

    public function autenticacion(Request $request)
    {
        return app(LoginController::class)
            ->login($request, app(GalyAccountBridge::class), '/dashboard');
    }

    public function registro()
    {
        $ajuste = Ajuste::first();

        return view('web.registro', compact('ajuste'));
    }

    public function crear_cuenta(Request $request, GalyAccountBridge $accounts)
    {
        // return response()->json($request->all());
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $accounts->register(
                trim((string) $request->input('name')),
                strtolower(trim((string) $request->input('email'))),
                (string) $request->input('password')
            );
        } catch (RuntimeException $exception) {
            report($exception);

            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['email' => 'No se pudo conectar con GALY para crear la cuenta. Inténtalo de nuevo.']);
        }

        return redirect('/dashboard');
    }

    public function informacion_personal(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.Auth::user()->id,
        ]);

        $user = Auth::user();
        app(GalyAccountBridge::class)->refreshCanonicalProfile(
            $user,
            (string) $request->input('name'),
            (string) $request->input('email')
        );

        return redirect()->back()->with('mensaje', 'Información personal actualizada correctamente.')->with('icono', 'success');
    }

    public function actualizar_password(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        $verified = password_verify($request->input('current_password'), $user->password);
        if (! $verified) {
            return redirect()->back()->withErrors(['current_password' => 'La contraseña actual es incorrecta.']);
        }

        app(GalyAccountBridge::class)->changePassword($user, (string) $request->input('password'));

        return redirect()->back()->with('mensaje', 'Contraseña actualizada correctamente.')->with('icono', 'success');
    }
}
