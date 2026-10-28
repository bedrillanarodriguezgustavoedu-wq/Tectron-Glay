<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\LoginController;
use App\Models\Ajuste;
use App\Models\Direccion;
use App\Models\Orden;
use App\Models\Pais;
use App\Models\ProductoFavorito;
use App\Services\Galy\GalyAccountBridge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DashboardController extends Controller
{
    public function index()
    {
        if (! Auth::check()) {
            return redirect('/web/login');
        }

        $ajuste = Ajuste::first();
        $galyUserId = Auth::user()->galy_user_id;

        // Sin cuenta GALY compartida no hay pedidos que mostrar: la consulta se
        // queda vacía en lugar de enseñar pedidos sueltos de otro cliente.
        $base = $galyUserId === null
            ? Orden::query()->whereRaw('1 = 0')
            : Orden::query()->where('usuario_id', $galyUserId);

        $total_pedidos = (clone $base)->count();

        $resumen = ['por_pagar' => 0, 'en_curso' => 0, 'entregados' => 0, 'cancelados' => 0];
        foreach ((clone $base)->selectRaw('estado, estado_pago, count(*) as cantidad')->groupBy('estado', 'estado_pago')->get() as $grupo) {
            $resumen[$this->agrupar_pedido_para_resumen($grupo->estado, $grupo->estado_pago)] += (int) $grupo->cantidad;
        }

        $pedido_por_pagar = (clone $base)
            ->where(function ($consulta) {
                $consulta->where(function ($q) {
                    $q->whereNull('estado')->orWhereNotIn('estado', ['CANCELADO', 'ENTREGADO']);
                })->where(function ($q) {
                    $q->whereNull('estado_pago')->orWhereNotIn('estado_pago', ['Pagado', 'pagado', 'completed', 'approved', 'paid']);
                });
            })
            // El aviso tiene que señalar el pendiente más reciente, y en los
            // pedidos de la app la fecha real vive en `fecha`, no en `created_at`.
            ->orderByDesc(DB::raw('COALESCE(created_at, fecha)'))
            ->first();

        $pedidos = $base
            ->with(['usuario', 'detalles.producto.imagenes', 'historial'])
            // Los pedidos de la app no tienen `created_at`: ordenar por esa
            // columna los manda siempre al final sin importar su fecha real.
            ->orderByDesc(DB::raw('COALESCE(created_at, fecha)'))
            ->paginate(10)
            ->withQueryString();

        $total_direcciones = Direccion::where('usuario_id', Auth::user()->id)->count();
        $direcciones = Direccion::where('usuario_id', Auth::user()->id)
            ->with(['provincia', 'departamento'])
            ->orderByDesc('es_predeterminada')
            ->orderByDesc('id')
            ->limit(2)
            ->get();

        $total_favoritos = $galyUserId === null ? 0 : ProductoFavorito::where('usuario_id', $galyUserId)->count();
        $favoritos = $galyUserId === null
            ? collect()
            : ProductoFavorito::where('usuario_id', $galyUserId)
                ->with('producto.imagenes')
                ->orderByDesc('id')
                ->limit(4)
                ->get();

        return view('web.dashboard', compact(
            'ajuste',
            'total_pedidos',
            'resumen',
            'pedido_por_pagar',
            'pedidos',
            'direcciones',
            'total_direcciones',
            'favoritos',
            'total_favoritos',
        ));
    }

    /**
     * Las cuatro cifras del resumen tienen que cerrar con el total, así que cada
     * pedido cae en un solo cubo: manda la etapa de entrega y solo si el pedido
     * sigue en camino decide el estado del pago.
     */
    private function agrupar_pedido_para_resumen(?string $estado, ?string $estadoPago): string
    {
        $estado = strtoupper(trim((string) $estado));

        if ($estado === 'CANCELADO') {
            return 'cancelados';
        }

        if ($estado === 'ENTREGADO') {
            return 'entregados';
        }

        $pagado = in_array(strtolower(trim((string) $estadoPago)), ['pagado', 'completed', 'approved', 'paid'], true);

        return $pagado ? 'en_curso' : 'por_pagar';
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
            'telefono' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9\s().-]{7,20}$/'],
            'dni' => ['nullable', 'string', 'regex:/^\d{8}$/'],
        ], [
            'telefono.regex' => 'Ese teléfono no parece válido: escribe al menos 7 dígitos, por ejemplo 987654321.',
            'dni.regex' => 'El DNI debe tener exactamente 8 dígitos, sin letras ni espacios.',
        ]);

        $user = Auth::user();
        $telefono = trim((string) $request->input('telefono'));
        $dni = trim((string) $request->input('dni'));

        app(GalyAccountBridge::class)->refreshCanonicalProfile(
            $user,
            (string) $request->input('name'),
            (string) $request->input('email'),
            $telefono
        );

        // `dni` solo vive en la web (la app no tiene esa columna) y se guarda
        // cifrado por el cast del modelo.
        $user->dni = $dni !== '' ? $dni : null;
        $user->save();

        return redirect()->back()->with('mensaje', 'Información personal actualizada correctamente.')->with('icono', 'success');
    }

    public function actualizar_foto(Request $request)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'foto.required' => 'Elige una imagen antes de guardar.',
            'foto.image' => 'Ese archivo no es una imagen. Sube una foto en JPG, PNG o WEBP.',
            'foto.mimes' => 'La foto debe estar en formato JPG, PNG o WEBP.',
            'foto.max' => 'La foto no puede pesar más de 2 MB.',
        ]);

        $usuario = $request->user();
        $anterior = $usuario->foto;
        $nueva = $request->file('foto')->store('avatares', 'public');

        // El disco público está con `throw => false`, así que un fallo de disco
        // devuelve false en lugar de lanzar: sin esta guarda la foto quedaría
        // apuntando a un archivo inexistente.
        if ($nueva === false) {
            return redirect()->back()->withErrors(['foto' => 'No se pudo guardar la imagen. Inténtalo de nuevo.']);
        }

        $usuario->foto = $nueva;
        $usuario->save();

        // Primero se guarda la nueva y después se borra la vieja: si el borrado
        // falla, el cliente sigue viendo una foto y no un hueco.
        if ($anterior && Storage::disk('public')->exists($anterior)) {
            Storage::disk('public')->delete($anterior);
        }

        return redirect()->back()->with('mensaje', 'Tu foto de perfil se actualizó.')->with('icono', 'success');
    }

    public function quitar_foto(Request $request)
    {
        $usuario = $request->user();
        $anterior = $usuario->foto;

        $usuario->foto = null;
        $usuario->save();

        if ($anterior && Storage::disk('public')->exists($anterior)) {
            Storage::disk('public')->delete($anterior);
        }

        return redirect()->back()
            ->with('mensaje', 'Quitamos tu foto: en su lugar se mostrarán tus iniciales.')
            ->with('icono', 'success');
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
