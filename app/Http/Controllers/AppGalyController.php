<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Galy\GalyApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

/**
 * Administración de la app GALY desde el panel web.
 *
 * La app Android y esta tienda comparten la base `ecommerce`: los clientes de
 * la app viven en `usuarios`, sus pedidos en `pedidos` y sus comentarios en
 * `comentarios`. Esta sección expone esas tablas en el panel web para que el
 * mismo administrador pueda gestionar ambos canales desde un solo lugar.
 */
class AppGalyController extends Controller
{
    private function galy()
    {
        return DB::connection('galy');
    }

    private function hasTable(string $table): bool
    {
        return Schema::connection('galy')->hasTable($table);
    }

    private function hasColumn(string $table, string $column): bool
    {
        return $this->hasTable($table)
            && Schema::connection('galy')->hasColumn($table, $column);
    }

    public function index(): View
    {
        $metaMensual = (float) config('galy.meta_mensual', 100000);
        $estadosCancelados = ['CANCELADO', 'CANCELADA', 'ANULADO'];

        $ventas = 0.0;
        $pedidosTotal = 0;
        $pedidosMes = 0;
        $ventasMes = 0.0;
        $clientesTotal = 0;
        $productosTotal = 0;
        $stockCritico = 0;
        $inventarioCosto = 0.0;
        $utilidadPotencial = 0.0;
        $comentariosTotal = 0;
        $ventasDistrito = [];
        $productosCriticos = [];
        $ultimosPedidos = [];
        $topProductos = [];

        if ($this->hasTable('pedidos')) {
            $pedidos = $this->galy()->table('pedidos');
            $pedidosTotal = (int) (clone $pedidos)->count();
            $ventas = (float) (clone $pedidos)
                ->whereNotIn(DB::raw('UPPER(COALESCE(estado, \'\'))'), $estadosCancelados)
                ->sum('total');

            if ($this->hasColumn('pedidos', 'created_at')) {
                $pedidosMes = (int) (clone $pedidos)
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->count();
                $ventasMes = (float) (clone $pedidos)
                    ->whereNotIn(DB::raw('UPPER(COALESCE(estado, \'\'))'), $estadosCancelados)
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->sum('total');
            }

            if ($this->hasColumn('pedidos', 'distrito')) {
                $ventasDistrito = (clone $pedidos)
                    ->selectRaw('COALESCE(NULLIF(TRIM(distrito), \'\'), \'Sin distrito\') AS label, SUM(total) AS monto')
                    ->whereNotIn(DB::raw('UPPER(COALESCE(estado, \'\'))'), $estadosCancelados)
                    ->groupBy('label')
                    ->orderByDesc('monto')
                    ->limit(6)
                    ->get()
                    ->map(fn ($row) => [
                        'label' => (string) $row->label,
                        'monto' => (float) $row->monto,
                    ])
                    ->all();
            }

            if ($this->hasColumn('pedidos', 'created_at')) {
                $ultimosPedidos = (clone $pedidos)
                    ->select(['id', 'usuario_id', 'total', 'estado', 'estado_orden', 'metodo_pago', 'created_at'])
                    ->orderByDesc('id')
                    ->limit(8)
                    ->get();
            }
        }

        if ($this->hasTable('usuarios')) {
            $clientesTotal = (int) $this->galy()->table('usuarios')->count();
        }

        if ($this->hasTable('productos')) {
            $productos = $this->galy()->table('productos');
            $productosTotal = (int) (clone $productos)->count();

            if ($this->hasColumn('productos', 'stock')) {
                $stockCritico = (int) (clone $productos)->where('stock', '<', 5)->count();
                $columnasCriticas = array_values(array_filter(
                    ['id', 'nombre', 'stock', 'imagen_url'],
                    fn (string $columna): bool => $this->hasColumn('productos', $columna)
                ));
                $productosCriticos = (clone $productos)
                    ->select($columnasCriticas)
                    ->where('stock', '<', 5)
                    ->orderBy('stock')
                    ->limit(8)
                    ->get();
            }

            if ($this->hasColumn('productos', 'precio_compra')) {
                $inventarioCosto = (float) (clone $productos)
                    ->selectRaw('COALESCE(SUM(stock * precio_compra), 0) AS total')
                    ->value('total');
            }

            if ($this->hasColumn('productos', 'precio_compra') && $this->hasColumn('productos', 'precio_normal')) {
                $utilidadPotencial = (float) (clone $productos)
                    ->selectRaw('COALESCE(SUM(stock * (precio_normal - precio_compra)), 0) AS total')
                    ->value('total');
            }
        }

        if ($this->hasTable('comentarios')) {
            $comentariosTotal = (int) $this->galy()->table('comentarios')->count();
        }

        if (
            $this->hasTable('pedido_productos')
            && $this->hasTable('productos')
            && $this->hasColumn('pedido_productos', 'producto_id')
            && $this->hasColumn('pedido_productos', 'cantidad')
            && $this->hasColumn('productos', 'nombre')
        ) {
            $topProductos = $this->galy()->table('pedido_productos as pp')
                ->join('productos as p', 'pp.producto_id', '=', 'p.id')
                ->selectRaw('p.id, p.nombre, SUM(pp.cantidad) AS total_vendido')
                ->groupBy('p.id', 'p.nombre')
                ->orderByDesc('total_vendido')
                ->limit(5)
                ->get();
        }

        $porcentajeMeta = $metaMensual > 0
            ? min(100, round(($ventasMes / $metaMensual) * 100, 1))
            : 0;

        $serieVentas = $this->serieVentas();
        $funciones = $this->funciones();

        return view('admin.app-galy.index', compact(
            'metaMensual',
            'porcentajeMeta',
            'ventas',
            'ventasMes',
            'pedidosTotal',
            'pedidosMes',
            'clientesTotal',
            'productosTotal',
            'stockCritico',
            'inventarioCosto',
            'utilidadPotencial',
            'comentariosTotal',
            'ventasDistrito',
            'productosCriticos',
            'ultimosPedidos',
            'topProductos',
            'serieVentas',
            'funciones',
        ));
    }

    public function clientes(Request $request): View
    {
        abort_unless($this->hasTable('usuarios'), 503, 'La tabla de clientes de la app no está disponible.');

        $buscar = trim((string) $request->get('buscar', ''));
        $filtro = (string) $request->get('filtro', '');
        $query = $this->galy()->table('usuarios')
            ->select([
                'id',
                'nombre',
                'email',
                'telefono',
                'direccion',
                'es_admin',
                'puntos',
                'imagen_url',
                'estado',
                'fecha_registro',
            ]);

        if ($filtro === 'admin') {
            $query->where('es_admin', 1);
        } elseif ($filtro === 'activos') {
            $query->where('estado', 1);
        } elseif ($filtro === 'inactivos') {
            $query->where('estado', 0);
        }

        if ($buscar !== '') {
            $query->where(function ($sub) use ($buscar): void {
                $sub->where('nombre', 'like', '%'.$buscar.'%')
                    ->orWhere('email', 'like', '%'.$buscar.'%')
                    ->orWhere('telefono', 'like', '%'.$buscar.'%');
            });
        }

        if ($this->hasTable('pedidos') && $this->hasColumn('pedidos', 'usuario_id')) {
            $query->selectSub(
                $this->galy()->table('pedidos')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('pedidos.usuario_id', 'usuarios.id'),
                'pedidos_count'
            );
            $query->selectSub(
                $this->galy()->table('pedidos')
                    ->selectRaw('COALESCE(SUM(total), 0)')
                    ->whereColumn('pedidos.usuario_id', 'usuarios.id')
                    ->whereNotIn(DB::raw('UPPER(COALESCE(estado, \'\'))'), ['CANCELADO', 'CANCELADA', 'ANULADO']),
                'total_gastado'
            );
        }

        $clientes = $query->orderByDesc('id')->paginate(15)->withQueryString();

        return view('admin.app-galy.clientes', compact('clientes', 'buscar', 'filtro'));
    }

    public function cliente(int $id): View
    {
        abort_unless($this->hasTable('usuarios'), 503, 'La tabla de clientes de la app no está disponible.');

        $cliente = $this->galy()->table('usuarios')->where('id', $id)->first();
        abort_if($cliente === null, 404);

        $cuentaWeb = User::withTrashed()->where('galy_user_id', $id)->first();

        $pedidos = collect();
        $totalGastado = 0.0;
        if ($this->hasTable('pedidos') && $this->hasColumn('pedidos', 'usuario_id')) {
            $pedidos = $this->galy()->table('pedidos')
                ->select(['id', 'total', 'estado', 'estado_orden', 'metodo_pago', 'created_at'])
                ->where('usuario_id', $id)
                ->orderByDesc('id')
                ->limit(10)
                ->get();
            $totalGastado = (float) $this->galy()->table('pedidos')
                ->where('usuario_id', $id)
                ->whereNotIn(DB::raw('UPPER(COALESCE(estado, \'\'))'), ['CANCELADO', 'CANCELADA', 'ANULADO'])
                ->sum('total');
        }

        return view('admin.app-galy.cliente', compact('cliente', 'cuentaWeb', 'pedidos', 'totalGastado'));
    }

    public function actualizarCliente(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->hasTable('usuarios'), 503, 'La tabla de clientes de la app no está disponible.');

        $cliente = $this->galy()->table('usuarios')->where('id', $id)->first();
        abort_if($cliente === null, 404);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('galy.usuarios', 'email')->ignore($id),
            ],
            'telefono' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:1000'],
            'puntos' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'es_admin' => ['nullable', 'boolean'],
            'estado' => ['nullable', 'boolean'],
        ]);

        $esAdmin = $request->boolean('es_admin');
        $estado = $request->boolean('estado', true);
        $puntos = (int) ($datos['puntos'] ?? $cliente->puntos ?? 0);

        $this->galy()->table('usuarios')->where('id', $id)->update([
            'nombre' => trim($datos['nombre']),
            'email' => strtolower(trim($datos['email'])),
            'telefono' => $datos['telefono'] ?? null,
            'direccion' => $datos['direccion'] ?? null,
            'puntos' => $puntos,
            'es_admin' => $esAdmin ? 1 : 0,
            'estado' => $estado ? 1 : 0,
            'updated_at' => now(),
        ]);

        // Si la cuenta de la app está vinculada a una cuenta web, se mantienen
        // ambos lados iguales para que el administrador no vea dos versiones.
        $cuentaWeb = User::withTrashed()->where('galy_user_id', $id)->first();
        if ($cuentaWeb !== null) {
            $cuentaWeb->name = trim($datos['nombre']);
            $cuentaWeb->telefono = $datos['telefono'] ?? $cuentaWeb->telefono;
            $cuentaWeb->galy_es_admin = $esAdmin;
            $cuentaWeb->estado = $estado;

            $nuevoEmail = strtolower(trim($datos['email']));
            $emailOcupado = User::withTrashed()
                ->whereRaw('LOWER(email) = ?', [$nuevoEmail])
                ->whereKeyNot($cuentaWeb->getKey())
                ->exists();
            if (! $emailOcupado) {
                $cuentaWeb->email = $nuevoEmail;
            }

            $cuentaWeb->save();
        }

        return redirect()
            ->route('admin.app-galy.cliente', $id)
            ->with('mensaje', 'Cliente GALY actualizado. Los cambios se aplican también en la app.');
    }

    public function notificaciones(): View
    {
        $totalDispositivos = 0;
        if ($this->hasTable('usuarios') && $this->hasColumn('usuarios', 'fcm_token')) {
            $totalDispositivos = (int) $this->galy()->table('usuarios')
                ->whereNotNull('fcm_token')
                ->where('fcm_token', '!=', '')
                ->count();
        }

        return view('admin.app-galy.notificaciones', compact('totalDispositivos'));
    }

    public function enviarNotificacion(Request $request, GalyApiClient $api): RedirectResponse
    {
        $datos = $request->validate([
            'titulo' => ['required', 'string', 'max:120'],
            'mensaje' => ['required', 'string', 'max:500'],
        ]);

        try {
            $resultado = $api->sendPush(trim($datos['titulo']), trim($datos['mensaje']));
        } catch (RuntimeException $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors(['notificacion' => $exception->getMessage()]);
        }

        return back()->with('mensaje', $resultado);
    }

    public function comentarios(Request $request): View
    {
        $buscar = trim((string) $request->get('buscar', ''));

        if (! $this->hasTable('comentarios')) {
            return view('admin.app-galy.comentarios', [
                'comentarios' => $this->paginadorVacio(20),
                'buscar' => $buscar,
            ]);
        }

        $conUsuarios = $this->hasTable('usuarios') && $this->hasColumn('usuarios', 'nombre');
        $conProductos = $this->hasTable('productos') && $this->hasColumn('productos', 'nombre');

        $query = $this->galy()->table('comentarios as c');
        if ($conUsuarios) {
            $query->leftJoin('usuarios as u', 'c.usuario_id', '=', 'u.id');
        }
        if ($conProductos) {
            $query->leftJoin('productos as p', 'c.producto_id', '=', 'p.id');
        }

        $query->select([
            'c.id',
            'c.producto_id',
            'c.usuario_id',
            'c.comentario',
            'c.estrellas',
            'c.fecha',
        ]);
        $query->selectRaw($conUsuarios ? 'u.nombre as usuario_nombre' : 'NULL as usuario_nombre');
        $query->selectRaw($conUsuarios && $this->hasColumn('usuarios', 'email') ? 'u.email as usuario_email' : 'NULL as usuario_email');
        $query->selectRaw($conProductos ? 'p.nombre as producto_nombre' : 'NULL as producto_nombre');
        $query->selectRaw($conProductos && $this->hasColumn('productos', 'imagen_url') ? 'p.imagen_url as producto_imagen' : 'NULL as producto_imagen');

        if ($buscar !== '') {
            $query->where(function ($sub) use ($buscar, $conUsuarios, $conProductos): void {
                $sub->where('c.comentario', 'like', '%'.$buscar.'%');
                if ($conUsuarios) {
                    $sub->orWhere('u.nombre', 'like', '%'.$buscar.'%');
                }
                if ($conProductos) {
                    $sub->orWhere('p.nombre', 'like', '%'.$buscar.'%');
                }
            });
        }

        $comentarios = $query->orderByDesc('c.id')->paginate(20)->withQueryString();

        return view('admin.app-galy.comentarios', compact('comentarios', 'buscar'));
    }

    public function actualizarComentario(Request $request, int $id): RedirectResponse
    {
        if (! $this->hasTable('comentarios')) {
            return back()->withErrors(['comentario' => 'La tabla de comentarios de la app no está disponible.']);
        }

        $datos = $request->validate([
            'comentario' => ['required', 'string', 'max:1000'],
        ], [], ['comentario' => 'el comentario']);

        $modificados = $this->galy()->table('comentarios')
            ->where('id', $id)
            ->update(['comentario' => trim($datos['comentario'])]);

        return $modificados > 0
            ? back()->with('mensaje', 'Comentario actualizado en la app GALY.')
            : back()->withErrors(['comentario' => 'No se encontró ese comentario en la app.']);
    }

    public function eliminarComentario(int $id): RedirectResponse
    {
        if (! $this->hasTable('comentarios')) {
            return back()->withErrors(['comentario' => 'La tabla de comentarios de la app no está disponible.']);
        }

        $this->galy()->table('comentarios')->where('id', $id)->delete();

        return back()->with('mensaje', 'Comentario eliminado de la app GALY.');
    }

    private function paginadorVacio(int $porPagina): \Illuminate\Pagination\LengthAwarePaginator
    {
        return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $porPagina, 1, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
    }

    public function pedidos(Request $request): View
    {
        abort_unless($this->hasTable('pedidos'), 503, 'La tabla de pedidos de la app no está disponible.');

        $query = $this->pedidosFiltrados($request);
        $buscar = trim((string) $request->get('buscar', ''));
        $estado = trim((string) $request->get('estado', ''));

        $estados = $this->hasColumn('pedidos', 'estado')
            ? $this->galy()->table('pedidos')
                ->selectRaw("DISTINCT UPPER(COALESCE(estado, '')) AS estado")
                ->whereRaw("COALESCE(estado, '') <> ''")
                ->orderBy('estado')
                ->limit(20)
                ->pluck('estado')
                ->all()
            : [];

        $pedidos = $query->orderByDesc('p.id')->paginate(15)->withQueryString();

        return view('admin.app-galy.pedidos', compact('pedidos', 'buscar', 'estado', 'estados'));
    }

    /**
     * Misma consulta para la pantalla y para el CSV: lo que ves es lo que se exporta.
     */
    private function pedidosFiltrados(Request $request)
    {
        $buscar = trim((string) $request->get('buscar', ''));
        $estado = trim((string) $request->get('estado', ''));

        $seleccion = ['p.id', 'p.total'];
        foreach (['usuario_id', 'estado', 'estado_orden', 'metodo_pago', 'distrito', 'cliente_nombre', 'cliente_email', 'fecha', 'created_at'] as $columna) {
            if ($this->hasColumn('pedidos', $columna)) {
                $seleccion[] = 'p.'.$columna;
            }
        }

        $query = $this->galy()->table('pedidos as p')->select($seleccion);

        $conUsuarios = $this->hasTable('usuarios') && $this->hasColumn('usuarios', 'nombre');
        if ($conUsuarios) {
            $query->leftJoin('usuarios as u', 'u.id', '=', 'p.usuario_id')
                ->selectRaw('u.nombre as usuario_nombre, u.email as usuario_email');
        } else {
            $query->selectRaw('NULL as usuario_nombre, NULL as usuario_email');
        }

        if ($estado !== '') {
            $query->whereRaw("UPPER(COALESCE(p.estado, '')) = ?", [mb_strtoupper($estado)]);
        }

        if ($buscar !== '') {
            $query->where(function ($sub) use ($buscar, $conUsuarios): void {
                $sub->where('p.id', $buscar);
                if ($this->hasColumn('pedidos', 'cliente_email')) {
                    $sub->orWhere('p.cliente_email', 'like', '%'.$buscar.'%');
                }
                if ($conUsuarios) {
                    $sub->orWhere('u.nombre', 'like', '%'.$buscar.'%')
                        ->orWhere('u.email', 'like', '%'.$buscar.'%');
                }
            });
        }

        return $query;
    }

    public function exportarPedidos(Request $request)
    {
        abort_unless($this->hasTable('pedidos'), 503, 'La tabla de pedidos de la app no está disponible.');

        $filas = $this->pedidosFiltrados($request)->orderByDesc('p.id')->limit(5000)->get();

        return $this->csv(
            'pedidos-galy-'.now()->format('Ymd-His').'.csv',
            ['N.º', 'Fecha', 'Cliente', 'Correo', 'Distrito', 'Pago', 'Estado', 'Estado web', 'Total S/'],
            $filas->map(fn ($p): array => [
                $p->id,
                $p->created_at ?? $p->fecha ?? '',
                $p->usuario_nombre ?: ($p->cliente_nombre ?: ''),
                $p->usuario_email ?: ($p->cliente_email ?: ''),
                $p->distrito ?: '',
                $p->metodo_pago ?: '',
                $p->estado ?: '',
                $p->estado_orden ?: '',
                number_format((float) $p->total, 2, '.', ''),
            ])->all()
        );
    }

    public function exportarClientes(Request $request)
    {
        abort_unless($this->hasTable('usuarios'), 503, 'La tabla de clientes de la app no está disponible.');

        $columnas = array_values(array_filter(
            ['id', 'nombre', 'email', 'telefono', 'puntos', 'es_admin', 'estado', 'fecha_registro'],
            fn (string $c): bool => $this->hasColumn('usuarios', $c)
        ));

        $buscar = trim((string) $request->get('buscar', ''));
        $filtro = (string) $request->get('filtro', '');

        $query = $this->galy()->table('usuarios')->select($columnas);

        if ($filtro === 'admin') {
            $query->where('es_admin', 1);
        } elseif ($filtro === 'activos') {
            $query->where('estado', 1);
        } elseif ($filtro === 'inactivos') {
            $query->where('estado', 0);
        }

        if ($buscar !== '') {
            $query->where(function ($sub) use ($buscar): void {
                $sub->where('nombre', 'like', '%'.$buscar.'%')
                    ->orWhere('email', 'like', '%'.$buscar.'%')
                    ->orWhere('telefono', 'like', '%'.$buscar.'%');
            });
        }

        $filas = $query->orderByDesc('id')->limit(5000)->get();

        return $this->csv(
            'clientes-galy-'.now()->format('Ymd-His').'.csv',
            ['N.º', 'Nombre', 'Correo', 'Teléfono', 'Puntos', 'Administrador', 'Estado', 'Registro'],
            $filas->map(fn (object $u): array => [
                $u->id ?? '',
                $u->nombre ?? '',
                $u->email ?? '',
                $u->telefono ?? '',
                $u->puntos ?? '',
                isset($u->es_admin) ? ((int) $u->es_admin === 1 ? 'sí' : 'no') : '',
                isset($u->estado) ? ((int) $u->estado === 1 ? 'activo' : 'inactivo') : '',
                $u->fecha_registro ?? '',
            ])->all()
        );
    }

    public function reportes(Request $request)
    {
        abort_unless($this->hasTable('pedidos'), 503, 'La tabla de pedidos de la app no está disponible.');

        $meses = ['mes' => (int) $request->get('mes', now()->month), 'anio' => (int) $request->get('anio', now()->year)];
        $meses['mes'] = max(1, min(12, $meses['mes']));
        $meses['anio'] = max(2000, min(2100, $meses['anio']));

        $cancelados = ['CANCELADO', 'CANCELADA', 'ANULADO'];
        $consulta = fn () => $this->galy()->table('pedidos');
        $porMes = fn () => (clone $consulta())
            ->whereYear('created_at', $meses['anio'])
            ->whereMonth('created_at', $meses['mes']);

        $tieneFechas = $this->hasColumn('pedidos', 'created_at');
        $ventasMes = $tieneFechas
            ? (float) $porMes()->whereNotIn(DB::raw('UPPER(COALESCE(estado, \'\'))'), $cancelados)->sum('total')
            : 0.0;
        $pedidosMes = $tieneFechas ? (int) $porMes()->count() : 0;
        $utilidadMes = 0.0;

        if (
            $tieneFechas
            && $this->hasTable('pedido_productos')
            && $this->hasTable('productos')
            && $this->hasColumn('productos', 'precio_compra')
        ) {
            $costoMes = (float) $porMes()
                ->join('pedido_productos as pp', 'pp.pedido_id', '=', 'pedidos.id')
                ->join('productos as p', 'p.id', '=', 'pp.producto_id')
                ->selectRaw('COALESCE(SUM(pp.cantidad * p.precio_compra), 0) AS total')
                ->value('total');
            $utilidadMes = round($ventasMes - $costoMes, 2);
        }

        $porDia = $tieneFechas
            ? $porMes()
                ->selectRaw("DATE(created_at) AS dia, COUNT(*) AS pedidos, SUM(total) AS monto")
                ->groupBy('dia')
                ->orderBy('dia')
                ->get()
                ->map(fn ($row) => [
                    'dia' => (string) $row->dia,
                    'pedidos' => (int) $row->pedidos,
                    'monto' => (float) $row->monto,
                ])
                ->all()
            : [];

        $topProductos = [];
        if (
            $this->hasTable('pedido_productos')
            && $this->hasTable('productos')
            && $this->hasColumn('productos', 'nombre')
        ) {
            $topProductos = $this->galy()->table('pedido_productos as pp')
                ->join('productos as p', 'pp.producto_id', '=', 'p.id')
                ->selectRaw('p.nombre, SUM(pp.cantidad) AS unidades')
                ->groupBy('p.nombre')
                ->orderByDesc('unidades')
                ->limit(10)
                ->get()
                ->all();
        }

        return view('admin.app-galy.reportes', [
            'mes' => $meses['mes'],
            'anio' => $meses['anio'],
            'ventasMes' => $ventasMes,
            'pedidosMes' => $pedidosMes,
            'utilidadMes' => $utilidadMes,
            'ticketPromedio' => $pedidosMes > 0 ? round($ventasMes / $pedidosMes, 2) : 0.0,
            'porDia' => $porDia,
            'topProductos' => $topProductos,
        ]);
    }

    /**
     * @param  array<int, string>  $cabecera
     * @param  array<int, array<int, string>>  $filas
     */
    private function csv(string $nombreArchivo, array $cabecera, array $filas)
    {
        return response()->streamDownload(function () use ($cabecera, $filas): void {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, $cabecera);
            foreach ($filas as $fila) {
                fputcsv($salida, $fila);
            }
            fclose($salida);
        }, $nombreArchivo, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function administradores(): View
    {
        abort_unless($this->hasTable('usuarios'), 503, 'La tabla de clientes de la app no está disponible.');

        $administradores = $this->galy()->table('usuarios')
            ->select(['id', 'nombre', 'email', 'telefono', 'estado', 'fecha_registro', 'updated_at'])
            ->where('es_admin', 1)
            ->orderByDesc('id')
            ->get();

        $pedidosPorAdmin = [];
        if ($this->hasTable('pedidos') && $this->hasColumn('pedidos', 'usuario_id')) {
            $pedidosPorAdmin = $this->galy()->table('pedidos')
                ->whereIn('usuario_id', $administradores->pluck('id')->all())
                ->selectRaw('usuario_id, COUNT(*) AS cantidad, COALESCE(SUM(total), 0) AS monto')
                ->groupBy('usuario_id')
                ->get()
                ->keyBy('usuario_id');
        }

        $sesionesPorAdmin = $this->sesionesActivasPorUsuario($administradores->pluck('id')->all());

        $vinculosWeb = User::whereNotNull('galy_user_id')
            ->get(['id', 'name', 'email', 'galy_user_id', 'galy_es_admin'])
            ->keyBy('galy_user_id');

        $totalClientes = (int) $this->galy()->table('usuarios')->count();

        return view('admin.app-galy.administradores', compact(
            'administradores',
            'pedidosPorAdmin',
            'sesionesPorAdmin',
            'vinculosWeb',
            'totalClientes',
        ));
    }

    public function crearAdministrador(Request $request): RedirectResponse
    {
        abort_unless($this->hasTable('usuarios'), 503, 'La tabla de clientes de la app no está disponible.');

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('galy.usuarios', 'email')],
            'password' => ['required', 'string', 'min:12', 'max:72', 'confirmed'],
        ], [
            'password.min' => 'La contraseña debe tener al menos 12 caracteres.',
        ]);

        $id = $this->galy()->table('usuarios')->insertGetId([
            'nombre' => trim($datos['nombre']),
            'email' => strtolower(trim($datos['email'])),
            'password' => Hash::make($datos['password']),
            'es_admin' => 1,
            'estado' => 1,
            'puntos' => 0,
            'fecha_registro' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('admin.app-galy.cliente', $id)
            ->with('mensaje', 'Administrador creado. Ya puede entrar a la app y al panel GALY con ese correo y contraseña.');
    }

    public function alternarAdministrador(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->hasTable('usuarios'), 503, 'La tabla de clientes de la app no está disponible.');

        $cliente = $this->galy()->table('usuarios')->where('id', $id)->first();
        abort_if($cliente === null, 404);

        $activar = $request->boolean('es_admin');

        if (! $activar && (int) $cliente->es_admin === 1) {
            $otros = (int) $this->galy()->table('usuarios')
                ->where('es_admin', 1)
                ->where('id', '!=', $id)
                ->count();
            if ($otros === 0) {
                return back()->withErrors([
                    'administrador' => 'La app debe conservar al menos un administrador. Crea otra cuenta administrativa antes de quitar este rango.',
                ]);
            }
        }

        $this->galy()->table('usuarios')->where('id', $id)->update([
            'es_admin' => $activar ? 1 : 0,
            'updated_at' => now(),
        ]);

        $cerradas = $activar ? 0 : $this->cerrarSesionesApp($id);

        $cuentaWeb = User::withTrashed()->where('galy_user_id', $id)->first();
        if ($cuentaWeb !== null) {
            $cuentaWeb->galy_es_admin = $activar;
            $cuentaWeb->save();
        }

        $mensaje = $activar
            ? $cliente->nombre.' ya es administrador de la app.'
            : $cliente->nombre.' dejó de ser administrador.';
        if (! $activar && $cerradas > 0) {
            $mensaje .= ' Se cerraron '.$cerradas.' sesión(es) de GALY.';
        }

        return back()->with('mensaje', $mensaje);
    }

    public function restablecerPassword(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->hasTable('usuarios'), 503, 'La tabla de clientes de la app no está disponible.');

        abort_if($this->galy()->table('usuarios')->where('id', $id)->doesntExist(), 404);

        $datos = $request->validate([
            'password' => ['required', 'string', 'min:12', 'max:72', 'confirmed'],
        ], [
            'password.min' => 'La contraseña debe tener al menos 12 caracteres.',
        ]);

        $this->galy()->table('usuarios')->where('id', $id)->update([
            'password' => Hash::make($datos['password']),
            'updated_at' => now(),
        ]);

        $cerradas = $this->cerrarSesionesApp($id);

        $mensaje = 'Contraseña actualizada en la app GALY.';
        if ($cerradas > 0) {
            $mensaje .= ' Se cerraron '.$cerradas.' sesión(es) activas.';
        }

        return back()->with('mensaje', $mensaje);
    }

    public function dispositivos(): View
    {
        abort_unless($this->hasTable('usuarios'), 503, 'La tabla de clientes de la app no está disponible.');

        $totalClientes = (int) $this->galy()->table('usuarios')->count();

        if (! $this->hasColumn('usuarios', 'fcm_token')) {
            return view('admin.app-galy.dispositivos', [
                'dispositivos' => $this->paginadorVacio(20),
                'totalClientes' => $totalClientes,
                'sinToken' => $totalClientes,
                'sinColumna' => true,
            ]);
        }

        $dispositivos = $this->galy()->table('usuarios')
            ->select(['id', 'nombre', 'email', 'fcm_token', 'es_admin', 'updated_at', 'fecha_registro'])
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString()
            ->through(function (object $usuario): object {
                $token = (string) $usuario->fcm_token;
                $usuario->token_corto = mb_substr($token, 0, 10).'…'.mb_substr($token, -6);

                return $usuario;
            });

        return view('admin.app-galy.dispositivos', [
            'dispositivos' => $dispositivos,
            'totalClientes' => $totalClientes,
            'sinToken' => $totalClientes - (int) $dispositivos->total(),
            'sinColumna' => false,
        ]);
    }

    public function revocarDispositivo(int $id): RedirectResponse
    {
        abort_unless(
            $this->hasTable('usuarios') && $this->hasColumn('usuarios', 'fcm_token'),
            503,
            'La app todavía no registra dispositivos en esta base.'
        );

        $afectados = $this->galy()->table('usuarios')
            ->where('id', $id)
            ->where('fcm_token', '!=', '')
            ->update(['fcm_token' => null, 'updated_at' => now()]);

        return back()->with(
            'mensaje',
            $afectados > 0
                ? 'Dispositivo desvinculado. La app volverá a registrarse cuando el cliente la abra.'
                : 'Ese cliente ya no tenía un dispositivo registrado.'
        );
    }

    /**
     * Ventas de los últimos siete días, usadas por el gráfico del resumen.
     *
     * @return list<array{label: string, monto: float}>
     */
    private function serieVentas(): array
    {
        if (! $this->hasTable('pedidos') || ! $this->hasColumn('pedidos', 'total')) {
            return [];
        }

        $columna = $this->hasColumn('pedidos', 'created_at') ? 'created_at'
            : ($this->hasColumn('pedidos', 'fecha') ? 'fecha' : null);

        if ($columna === null) {
            return [];
        }

        $inicio = now()->subDays(6)->startOfDay();

        $montos = $this->galy()->table('pedidos')
            ->selectRaw("DATE({$columna}) AS dia, SUM(total) AS monto")
            ->where($columna, '>=', $inicio)
            ->whereNotIn(DB::raw("UPPER(COALESCE(estado, ''))"), ['CANCELADO', 'CANCELADA', 'ANULADO'])
            ->groupByRaw("DATE({$columna})")
            ->get()
            ->keyBy('dia');

        $serie = [];
        for ($dia = $inicio->copy(); $dia->lte(now()->startOfDay()); $dia->addDay()) {
            $clave = $dia->toDateString();
            $serie[] = [
                'label' => $dia->format('d/m'),
                'monto' => (float) ($montos[$clave]->monto ?? 0),
            ];
        }

        return $serie;
    }

    /**
     * Funciones del administrador de la app, con su equivalente en el panel web.
     *
     * @return list<array{titulo: string, descripcion: string, icono: string, ruta: string, contador: ?int}>
     */
    private function funciones(): array
    {
        $definiciones = [
            ['titulo' => 'Agregar producto', 'descripcion' => 'Nuevo ítem del catálogo', 'icono' => 'bi-plus-square', 'ruta' => 'admin.productos.create', 'permiso' => 'Crear Producto', 'contador' => null],
            ['titulo' => 'Mi inventario', 'descripcion' => 'Productos y stock de la app', 'icono' => 'bi-box-seam', 'ruta' => 'admin.productos.index', 'permiso' => 'Listado de Productos', 'contador' => $this->conteo('productos')],
            ['titulo' => 'Categorías', 'descripcion' => 'Catálogo compartido', 'icono' => 'bi-tags', 'ruta' => 'admin.categorias.index', 'permiso' => 'Listado de Categorías', 'contador' => $this->conteo('categorias')],
            ['titulo' => 'Marcas', 'descripcion' => 'Marcas visibles en la app', 'icono' => 'bi-award', 'ruta' => 'admin.marcas.index', 'permiso' => 'Listado de Marcas', 'contador' => $this->conteo('marcas')],
            ['titulo' => 'Banners', 'descripcion' => 'Portada de la app y la web', 'icono' => 'bi-image', 'ruta' => 'admin.banners.index', 'permiso' => 'Listado de Banners', 'contador' => $this->conteo('banners')],
            ['titulo' => 'Ofertas', 'descripcion' => 'Campañas y descuentos', 'icono' => 'bi-lightning', 'ruta' => 'admin.ofertas.index', 'permiso' => 'Listado de Ofertas', 'contador' => $this->conteo('ofertas')],
            ['titulo' => 'Cupones', 'descripcion' => 'Códigos promocionales', 'icono' => 'bi-ticket-perforated', 'ruta' => 'admin.cupones.index', 'permiso' => 'Listado de Cupones', 'contador' => $this->conteo('cupones')],
            ['titulo' => 'Ventas de la app', 'descripcion' => 'Historial de pedidos', 'icono' => 'bi-receipt', 'ruta' => 'admin.app-galy.pedidos', 'permiso' => 'Listado de Pedidos', 'contador' => $this->conteo('pedidos')],
            ['titulo' => 'Reportes y ganancias', 'descripcion' => 'Ventas del mes, utilidad y CSV', 'icono' => 'bi-file-earmark-bar-graph', 'ruta' => 'admin.app-galy.reportes', 'permiso' => null, 'contador' => null],
            ['titulo' => 'Soporte técnico', 'descripcion' => 'Tickets y reparaciones', 'icono' => 'bi-tools', 'ruta' => 'admin.soporte.index', 'permiso' => 'Ver Soporte Técnico', 'contador' => $this->conteo('servicios_tecnicos')],
            ['titulo' => 'Reservas', 'descripcion' => 'Productos apartados', 'icono' => 'bi-bookmark-check', 'ruta' => 'admin.reservas.index', 'permiso' => 'Ver Reservas', 'contador' => $this->conteo('reservas_productos')],
            ['titulo' => 'Contacto', 'descripcion' => 'Teléfono, WhatsApp y redes', 'icono' => 'bi-headset', 'ruta' => 'admin.contacto.index', 'permiso' => 'Ver Contacto', 'contador' => null],
            ['titulo' => 'Nosotros', 'descripcion' => 'Información de la empresa', 'icono' => 'bi-info-circle', 'ruta' => 'admin.nosotros.index', 'permiso' => 'Ver Nosotros', 'contador' => null],
            ['titulo' => 'Mensajes recibidos', 'descripcion' => 'Formularios de la tienda', 'icono' => 'bi-envelope', 'ruta' => 'admin.mensajes.index', 'permiso' => 'Listado de Mensajes', 'contador' => $this->conteo('mensajes_contacto')],
            ['titulo' => 'Clientes de la app', 'descripcion' => 'Cuentas, puntos y pedidos', 'icono' => 'bi-people', 'ruta' => 'admin.app-galy.clientes', 'permiso' => null, 'contador' => $this->conteo('usuarios')],
            ['titulo' => 'Administradores GALY', 'descripcion' => 'Quién puede mandar en la app', 'icono' => 'bi-shield-lock', 'ruta' => 'admin.app-galy.administradores', 'permiso' => null, 'contador' => $this->conteo('usuarios', fn ($q) => $q->where('es_admin', 1))],
            ['titulo' => 'Dispositivos push', 'descripcion' => 'Teléfonos registrados', 'icono' => 'bi-phone-vibrate', 'ruta' => 'admin.app-galy.dispositivos', 'permiso' => null, 'contador' => $this->hasColumn('usuarios', 'fcm_token')
                ? $this->conteo('usuarios', fn ($q) => $q->whereNotNull('fcm_token')->where('fcm_token', '!=', ''))
                : null],
            ['titulo' => 'Comentarios', 'descripcion' => 'Reseñas de productos', 'icono' => 'bi-chat-left-text', 'ruta' => 'admin.app-galy.comentarios', 'permiso' => null, 'contador' => $this->conteo('comentarios')],
            ['titulo' => 'Notificaciones', 'descripcion' => 'Anuncios a todos los clientes', 'icono' => 'bi-bell', 'ruta' => 'admin.app-galy.notificaciones', 'permiso' => null, 'contador' => null],
        ];

        $funciones = [];
        foreach ($definiciones as $funcion) {
            if (! Route::has($funcion['ruta'])) {
                continue;
            }
            if ($funcion['permiso'] !== null && ! Gate::allows($funcion['permiso'])) {
                continue;
            }
            unset($funcion['permiso']);
            $funciones[] = $funcion;
        }

        return $funciones;
    }

    private function conteo(string $tabla, ?callable $filtro = null): ?int
    {
        if (! $this->hasTable($tabla)) {
            return null;
        }

        $query = $this->galy()->table($tabla);
        if ($filtro !== null) {
            $filtro($query);
        }

        return (int) $query->count();
    }

    /**
     * Sesiones de GALY abiertas por cada cuenta, contadas desde los tokens que
     * emiten la app y el panel de la app (los tres nombres son propios de GALY).
     *
     * @param  list<int>  $ids
     * @return array<int, int>
     */
    private function sesionesActivasPorUsuario(array $ids): array
    {
        if ($ids === [] || ! $this->hasTable('personal_access_tokens')) {
            return [];
        }

        return $this->galy()->table('personal_access_tokens')
            ->selectRaw('tokenable_id, COUNT(*) AS sesiones')
            ->where('tokenable_type', 'App\\Models\\User')
            ->whereIn('name', ['galy-mobile-and-admin', 'galy-web-sso', 'galy-admin-web-link'])
            ->whereIn('tokenable_id', $ids)
            ->groupBy('tokenable_id')
            ->pluck('sesiones', 'tokenable_id')
            ->map(fn ($sesiones) => (int) $sesiones)
            ->all();
    }

    private function cerrarSesionesApp(int $id): int
    {
        if (! $this->hasTable('personal_access_tokens')) {
            return 0;
        }

        return $this->galy()->table('personal_access_tokens')
            ->where('tokenable_type', 'App\\Models\\User')
            ->where('tokenable_id', $id)
            ->whereIn('name', ['galy-mobile-and-admin', 'galy-web-sso', 'galy-admin-web-link'])
            ->delete();
    }
}
