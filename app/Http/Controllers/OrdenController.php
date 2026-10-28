<?php

namespace App\Http\Controllers;

use App\Mail\CompraConfirmada;
use App\Models\Ajuste;
use App\Models\IntentoPagoMercadoPago;
use App\Models\Orden;
use App\Models\PagoManual;
use App\Services\MercadoPagoService;
use App\Services\ReservaProductoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class OrdenController extends Controller
{
    public function confirmacion($id)
    {
        $usuarioId = auth()->user()->resolveGalyUserId();
        abort_if($usuarioId === null, 403);

        $orden = Orden::with(['detalles.producto', 'pagoManual'])
            ->where('usuario_id', $usuarioId)
            ->findOrFail($id);
        $ajuste = Ajuste::first();
        $pagoManual = $orden->pagoManual;

        return view('web.orden_completado', compact('orden', 'ajuste', 'pagoManual'));
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $buscar = $request->get('buscar');
        $query = Orden::with(['usuario', 'detalles.producto'])
            ->orderByDesc(DB::raw('COALESCE(created_at, fecha)'));
        // return response()->json($ordenes);

        if ($buscar) {
            $query->where(function ($q) use ($buscar) {

                $q->whereHas('usuario', function ($userQuery) use ($buscar) {
                    $userQuery->where('name', 'like', '%'.$buscar.'%')
                        ->orWhere('email', 'like', '%'.$buscar.'%');
                });

                $q->orWhereHas('detalles.producto', function ($productQuery) use ($buscar) {
                    $productQuery->where('nombre', 'like', '%'.$buscar.'%');
                });
            });
        }

        $pedidos = $query->paginate(5);
        $usuariosGaly = DB::connection('galy')
            ->table('usuarios')
            ->whereIn('id', $pedidos->getCollection()->pluck('usuario_id')->filter()->unique())
            ->get(['id', 'nombre', 'email'])
            ->keyBy('id');

        return view('admin.pedidos.index', compact('pedidos', 'usuariosGaly'));
    }

    public function pagos(Request $request, MercadoPagoService $mercadoPago)
    {
        $validated = $request->validate([
            'buscar' => 'nullable|string|max:100',
            'estado' => 'nullable|in:pendiente,pagado,reembolsado,cancelado',
            'metodo' => 'nullable|in:yape,plin,bcp,tarjeta',
            'revision' => 'nullable|in:por_revisar,esperando,confirmado,anteriores,tarjetas,todos',
        ]);

        $query = Orden::with(['usuario', 'pagoManual', 'intentosPago'])
            ->orderByDesc(DB::raw('COALESCE(created_at, fecha)'));
        $revision = $validated['revision'] ?? 'esperando';

        if (($validated['metodo'] ?? null) === 'tarjeta' && ! $request->has('revision')) {
            $revision = 'tarjetas';
        }
        $metodosManuales = ['yape', 'plin', 'bcp'];
        $pagosManuales = fn () => Orden::query()
            ->whereIn(DB::raw('LOWER(metodo_pago)'), $metodosManuales);
        $pagosConTarjeta = fn () => Orden::query()
            ->whereRaw('LOWER(metodo_pago) = ?', ['tarjeta']);
        $pagosPorRevisar = fn ($orders) => $orders
            ->whereIn(DB::raw('LOWER(estado_pago)'), ['pendiente', 'pendiente de pago'])
            ->whereHas('pagoManual', fn ($payments) => $payments
                ->where('estado', 'comprobante_enviado')
                ->whereNotNull('comprobante_path')
                ->where('comprobante_path', '!=', ''));

        $resumenPagos = [
            'por_revisar' => $pagosPorRevisar($pagosManuales())->count(),
            'esperando' => $pagosManuales()
                ->whereIn(DB::raw('LOWER(estado_pago)'), ['pendiente', 'pendiente de pago'])
                ->whereHas('pagoManual', fn ($payments) => $payments
                    ->whereIn('estado', ['pendiente', 'comprobante_enviado'])
                    ->where(fn ($receipt) => $receipt
                        ->whereNull('comprobante_path')
                        ->orWhere('comprobante_path', '')))
                ->count(),
            'confirmado' => $pagosManuales()
                ->whereHas('pagoManual', fn ($payments) => $payments->where('estado', 'confirmado'))
                ->count(),
            'anteriores' => $pagosManuales()->whereDoesntHave('pagoManual')->count(),
            'tarjetas' => $pagosConTarjeta()
                ->whereNotIn(DB::raw('LOWER(estado_pago)'), ['pagado', 'completed', 'approved'])
                ->count(),
        ];

        if (! empty($validated['buscar'])) {
            $buscar = trim($validated['buscar']);
            $query->where(function ($orders) use ($buscar): void {
                if (ctype_digit($buscar)) {
                    $orders->whereKey((int) $buscar);
                }

                $orders->orWhere('transaccion_id', 'like', '%'.$buscar.'%');
            });
        }

        if (! empty($validated['estado'])) {
            $paymentStatuses = match ($validated['estado']) {
                'pendiente' => ['pendiente', 'pendiente de pago'],
                'pagado' => ['pagado', 'completed', 'approved'],
                default => [$validated['estado']],
            };
            $query->whereIn(DB::raw('LOWER(estado_pago)'), $paymentStatuses);
        }

        if (! empty($validated['metodo'])) {
            $query->whereRaw('LOWER(metodo_pago) = ?', [$validated['metodo']]);
        }

        if ($revision === 'tarjetas') {
            $query->whereRaw('LOWER(metodo_pago) = ?', ['tarjeta']);
        } elseif ($revision !== 'todos') {
            $query->whereIn(DB::raw('LOWER(metodo_pago)'), $metodosManuales);

            match ($revision) {
                'por_revisar' => $pagosPorRevisar($query),
                'esperando' => $query
                    ->whereIn(DB::raw('LOWER(estado_pago)'), ['pendiente', 'pendiente de pago'])
                    ->whereHas('pagoManual', fn ($payments) => $payments
                        ->whereIn('estado', ['pendiente', 'comprobante_enviado'])
                        ->where(fn ($receipt) => $receipt
                            ->whereNull('comprobante_path')
                            ->orWhere('comprobante_path', ''))),
                'confirmado' => $query->whereHas('pagoManual', fn ($payments) => $payments->where('estado', 'confirmado')),
                'anteriores' => $query->whereDoesntHave('pagoManual'),
            };
        }

        $pagos = $query->paginate(15)->withQueryString();
        $usuariosGaly = DB::connection('galy')
            ->table('usuarios')
            ->where(function ($users) use ($pagos): void {
                $users->whereIn('id', $pagos->getCollection()->pluck('usuario_id')->filter()->unique());

                $emails = $pagos->getCollection()->pluck('cliente_email')->filter()->unique();
                if ($emails->isNotEmpty()) {
                    $users->orWhereIn('email', $emails);
                }
            })
            ->get(['id', 'nombre', 'email'])
            ->keyBy(fn ($user) => strtolower($user->email));
        $usuariosGalyPorId = DB::connection('galy')
            ->table('usuarios')
            ->whereIn('id', $pagos->getCollection()->pluck('usuario_id')->filter()->unique())
            ->get(['id', 'nombre', 'email'])
            ->keyBy('id');

        $enlacePublico = $mercadoPago->habilitado() ? $mercadoPago->estadoEnlacePublico() : null;

        return view('admin.pagos.index', compact(
            'pagos', 'usuariosGaly', 'usuariosGalyPorId', 'resumenPagos', 'revision', 'enlacePublico'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($id)
    {
        $pedido = Orden::with(['usuario.roles', 'detalles', 'direccion.pais', 'direccion.departamento', 'direccion.provincia'])->findOrFail($id);
        $usuarioGaly = $pedido->usuario_id
            ? DB::connection('galy')
                ->table('usuarios')
                ->where('id', $pedido->usuario_id)
                ->first(['id', 'nombre', 'email'])
            : null;
        if (! $usuarioGaly && filled($pedido->cliente_email)) {
            $usuarioGaly = DB::connection('galy')
                ->table('usuarios')
                ->where('email', $pedido->cliente_email)
                ->first(['id', 'nombre', 'email']);
        }

        return view('admin.pedidos.create', compact('pedido', 'usuarioGaly'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $id)
    {
        return $this->avanzarEstado($request, $id);
    }

    /**
     * Advance the order state sequentially.
     */
    public function avanzarEstado(Request $request, $id)
    {
        $orden = Orden::with('usuario')->findOrFail($id);
        $ajuste = Ajuste::first();

        if (in_array(strtolower((string) $orden->metodo_pago), ['yape', 'plin', 'bcp'], true)
            && ! in_array(strtolower((string) $orden->estado_pago), ['pagado', 'completed', 'approved'], true)) {
            return redirect()->back()
                ->with('mensaje', 'No se puede avanzar el pedido: primero confirma que la transferencia fue recibida.')
                ->with('icono', 'warning');
        }

        $siguiente_estado = $orden->getSiguienteEstado();
        if (!$siguiente_estado) {
            return redirect()->back()
                ->with('mensaje', 'El pedido ya se encuentra en su estado final.')
                ->with('icono', 'warning');
        }

        $estado_anterior = $orden->estado_orden;
        
        // Update order status and note
        $orden->estado_orden = $siguiente_estado;
        if ($request->has('nota') && !empty($request->input('nota'))) {
            $orden->nota = $request->input('nota');
        }
        $orden->save();

        // Create history log entry
        $historial = new \App\Models\PedidoHistorial();
        $historial->orden_id = $orden->id;
        $historial->estado_anterior = $estado_anterior;
        $historial->estado_nuevo = $siguiente_estado;
        $historial->fecha = now()->toDateString();
        $historial->hora = now()->toTimeString();
        $historial->nota = $request->input('nota') ?? ('Pedido cambiado a: ' . $siguiente_estado);
        $historial->usuario_id = \Illuminate\Support\Facades\Auth::user()->galy_user_id;
        $historial->save();

        // Send email notification
        try {
            Mail::to($orden->usuario->email)->send(new \App\Mail\EstadoPedidoActualizadoMail($orden, $siguiente_estado, $ajuste));
        } catch (\Exception $e) {
            \Log::error("Error al enviar correo de actualización de pedido: " . $e->getMessage());
        }

        return redirect()->route('admin.pedidos.create', $orden->id)
            ->with('mensaje', 'El pedido ha avanzado a: ' . $siguiente_estado)
            ->with('icono', 'success');
    }

    /**
     * Create an unpaid order for a manual Yape, Plin or BCP transfer, or a card
     * order that continues on Mercado Pago.
     */
    public function crearPedidoPagoManual(Request $request, ReservaProductoService $reservations, MercadoPagoService $mercadoPago)
    {
        // Solo se exige un punto cuando la tienda publicó al menos uno: mientras
        // no existan, el carrito muestra la dirección general de Ajustes y el
        // cliente no tiene nada que elegir. `deTienda()` deja fuera las agencias
        // de provincias, que son otra decisión y otra modalidad.
        $tablaPuntos = Schema::hasTable('puntos_recojo');
        $hayPuntosRecojo = $tablaPuntos && \App\Models\PuntoRecojo::activos()->deTienda()->exists();

        // Las agencias dependen de la ciudad del pedido: una agencia de otra
        // provincia no sirve, así que se filtra por la provincia de la dirección
        // que el cliente acaba de elegir.
        $provinciaDestino = $tablaPuntos && $request->input('modalidad_entrega') === 'provincia' && $request->input('direccion_id')
            ? \App\Models\Direccion::where('usuario_id', auth()->id())
                ->whereKey($request->input('direccion_id'))
                ->value('provincia_id')
            : null;
        $hayAgenciasEnDestino = $provinciaDestino
            && \App\Models\PuntoRecojo::activos()->where('provincia_id', $provinciaDestino)->exists();

        $reglas = [
            'name' => 'required|string|max:255',
            'dni' => ['required', 'regex:/^[0-9]{8}$/'],
            'telefono' => ['required', 'regex:/^\+?[0-9\s().-]{7,20}$/'],
            'privacy_consent' => 'required|accepted',
            'direccion_id' => 'required_unless:modalidad_entrega,recojo|nullable|integer',
            'shipping_option' => 'nullable|string|in:standard,express,free,pickup,provincia',
            'modalidad_entrega' => 'required|string|in:envio,recojo,provincia',
            // `bcp` salió de la lista: la tienda retiró la transferencia del
            // carrito, y aceptar el valor por POST crearía un pedido cuyo destino
            // ya no se le muestra al cliente.
            'metodo_pago' => 'required|string|in:yape,plin,tarjeta',
        ];

        if ($hayPuntosRecojo) {
            $reglas['punto_recojo_id'] = [
                'required_if:modalidad_entrega,recojo', 'nullable', 'integer',
                \Illuminate\Validation\Rule::exists('puntos_recojo', 'id')
                    ->where('activo', true)->whereNull('provincia_id'),
            ];
        }

        if ($hayAgenciasEnDestino) {
            $reglas['agencia_provincia_id'] = [
                'required', 'integer',
                \Illuminate\Validation\Rule::exists('puntos_recojo', 'id')
                    ->where('activo', true)->where('provincia_id', $provinciaDestino),
            ];
        }

        $validated = $request->validate($reglas, [
            'name.required' => 'Ingresa tu nombre completo antes de continuar.',
            'dni.required' => 'Ingresa tu DNI para registrar el pedido.',
            'dni.regex' => 'El DNI debe contener exactamente 8 números.',
            'telefono.required' => 'Ingresa un teléfono de contacto.',
            'telefono.regex' => 'Ingresa un número de teléfono válido.',
            'privacy_consent.accepted' => 'Debes leer y aceptar la Política de Privacidad para continuar.',
            'direccion_id.required' => 'Registra y selecciona una dirección antes de continuar.',
            'direccion_id.integer' => 'Selecciona una dirección válida.',
            'punto_recojo_id.required_if' => 'Elige en qué punto quieres recoger tu pedido.',
            'punto_recojo_id.exists' => 'Ese punto de recojo ya no está disponible. Elige otro.',
            'agencia_provincia_id.required' => 'Elige en qué agencia de tu ciudad quieres recoger tu pedido.',
            'agencia_provincia_id.exists' => 'Esa agencia no atiende en la ciudad de tu dirección. Elige otra.',
        ]);

        if (! auth()->user()->google_id || ! auth()->user()->email_verified_at) {
            return redirect()->route('web.login')
                ->with('google_login_error', 'Verifica tu correo iniciando sesión con Google antes de pagar.');
        }

        $usuarioId = auth()->user()->resolveGalyUserId();
        if ($usuarioId === null) {
            return redirect()->route('web.carrito.index')
                ->with('mensaje', 'No se encontró una cuenta GALY vinculada a este correo. Inicia sesión con tu cuenta GALY para compartir el carrito.')
                ->with('icono', 'warning');
        }

        $ajuste = Ajuste::first();
        $metodo = $validated['metodo_pago'];

        if ($metodo === 'tarjeta') {
            if (! $ajuste?->web_mercadopago_activa || ! $mercadoPago->sePuedeOfrecer()) {
                return redirect()->route('web.carrito.index')
                    ->with('mensaje', 'El pago con tarjeta no está disponible por ahora. Elige Yape o Plin.')
                    ->with('icono', 'warning');
            }
        } else {
            $motivo = Ajuste::motivoParaOcultarPagoWeb($ajuste, $metodo);
            if ($motivo !== null) {
                Log::warning('Un cliente pidió un método de pago manual cuyo destino no es fiable.', [
                    'metodo' => $metodo,
                    'motivo' => $motivo,
                    'usuario_id' => $usuarioId,
                ]);

                return redirect()->route('web.carrito.index')
                    ->with('mensaje', 'Elige otro método de pago por ahora: la tienda aún no puede recibir transferencias por '
                        .ucfirst($metodo).'.')
                    ->with('icono', 'warning');
            }
        }

        $modalidad = $validated['modalidad_entrega'];
        // En recojo el cliente no necesita dirección: el pedido se retira en la
        // tienda, así que `direccion_id` llega vacío a propósito.
        $direccion = $modalidad === 'recojo'
            ? null
            : \App\Models\Direccion::where('usuario_id', auth()->id())
                ->whereKey($validated['direccion_id'] ?? null)
                ->firstOrFail();
        $direccionId = $direccion?->id;
        $punto = $modalidad === 'recojo' && $hayPuntosRecojo
            ? \App\Models\PuntoRecojo::activos()->deTienda()->findOrFail($validated['punto_recojo_id'])
            : null;
        $agencia = $hayAgenciasEnDestino
            ? \App\Models\PuntoRecojo::activos()->where('provincia_id', $provinciaDestino)
                ->findOrFail($validated['agencia_provincia_id'])
            : null;
        $direccionTexto = match (true) {
            // La agencia reemplaza el texto del destino: la calle del cliente ya
            // queda ligada por `direccion_id`, y aquí importa dónde está la caja.
            $agencia !== null => $agencia->texto_para_pedido,
            $punto !== null => $punto->texto_para_pedido,
            $modalidad === 'recojo' => 'Recojo en Tienda',
            default => $direccion->getFormattedAddress(),
        };

        $carritos = \App\Models\Carrito::where('usuario_id', $usuarioId)->with('producto')->get();
        if ($carritos->isEmpty()) {
            return redirect()->route('web.carrito.index')
                ->with('mensaje', 'El carrito está vacío.')
                ->with('icono', 'warning');
        }

        $firmaCarrito = $carritos
            ->map(fn ($item) => ((int) $item->producto_id).':'.((int) $item->cantidad))
            ->sort()
            ->values()
            ->implode('|');

        $subtotal = 0;
        foreach ($carritos as $item) {
            if (! $item->producto) {
                return redirect()->route('web.carrito.index')
                    ->with('mensaje', 'Uno de los productos del carrito ya no está disponible. Actualiza el carrito e inténtalo de nuevo.')
                    ->with('icono', 'warning');
            }

            $stockDisponible = $item->producto->stock + $reservations->activeQuantityForProduct(
                $usuarioId,
                (int) $item->producto_id
            );
            if ($item->cantidad > $stockDisponible) {
                return redirect()->route('web.carrito.index')
                    ->with('mensaje', "El producto {$item->producto->nombre} supera el stock disponible (Máx: {$stockDisponible}).")
                    ->with('icono', 'warning');
            }

            $precio = $item->producto->en_oferta && $item->producto->precio_oferta > 0
                ? $item->producto->precio_oferta
                : $item->producto->precio_venta;
            $subtotal += $precio * $item->cantidad;
        }

        $reglasEnvio = (array) config('tienda.envio');
        $costoEnvio = match (true) {
            // Recojo no paga envío; provincias tampoco se cobra aquí: su tarifa
            // no es fija y se coordina con el cliente antes de despachar.
            $modalidad !== 'envio' => 0.0,
            ($validated['shipping_option'] ?? 'standard') === 'express' => (float) $reglasEnvio['costo_express'],
            $subtotal < (float) $reglasEnvio['umbral_gratis'] => (float) $reglasEnvio['costo_estandar'],
            default => 0.0,
        };

        // El cupón se vuelve a resolver contra el subtotal real de este carrito.
        // Si el código dejó de ser válido se aborta el pedido: continuar sin el
        // descuento cobraría más de lo que el cliente vio en pantalla.
        $codigoCupon = (string) session('carrito_cupon', '');
        $descuento = 0.0;
        $cuponAplicado = null;
        if ($codigoCupon !== '') {
            $resuelto = \App\Models\Cupon::aplicar($codigoCupon, (float) $subtotal);
            if ($resuelto['mensaje'] !== null) {
                session()->forget('carrito_cupon');

                return redirect()->route('web.carrito.index')
                    ->with('mensaje', 'El cupón '.$codigoCupon.' ya no está disponible: '.$resuelto['mensaje'].' No creamos el pedido para no cobrarte un precio distinto al que viste.')
                    ->with('icono', 'warning');
            }

            $descuento = (float) $resuelto['descuento'];
            $cuponAplicado = $resuelto['codigo'];
        }

        $total = $subtotal - $descuento + $costoEnvio;
        if ($total <= 0) {
            return redirect()->route('web.carrito.index')
                ->with('mensaje', 'El total del pedido debe ser mayor a cero.')
                ->with('icono', 'warning');
        }

        auth()->user()->forceFill([
            'name' => trim($validated['name']),
            'dni' => $validated['dni'],
            'telefono' => trim($validated['telefono']),
            'checkout_privacy_accepted_at' => now(),
        ])->save();

        DB::connection('galy')->table('usuarios')
            ->where('id', $usuarioId)
            ->update(['nombre' => trim($validated['name'])]);

        $intento = null;
        if ($metodo === 'tarjeta') {
            $intento = $mercadoPago->abrirCheckout([
                'usuario_id' => $usuarioId,
                'items' => $carritos->map(fn ($item) => [
                    'producto_id' => (int) $item->producto_id,
                    'nombre' => $item->producto->nombre,
                    'cantidad' => (int) $item->cantidad,
                    'precio' => (float) ($item->producto->en_oferta && $item->producto->precio_oferta > 0
                        ? $item->producto->precio_oferta
                        : $item->producto->precio_venta),
                ])->all(),
                'direccion_id' => $direccionId,
                'direccion_envio' => $direccionTexto,
                'modalidad_entrega' => $modalidad,
                'monto' => (float) $total,
                'divisa' => (string) ($ajuste?->divisa ?: config('services.mercadopago.currency')),
                'correo' => auth()->user()->email,
            ]);

            if ($intento->estado === IntentoPagoMercadoPago::ESTADO_FALLIDO) {
                return redirect()->route('web.carrito.index')
                    ->with('mensaje', 'No pudimos abrir el pago con tarjeta. Tu carrito sigue intacto: elige otro método o inténtalo de nuevo.')
                    ->with('icono', 'error');
            }
        }

        try {
            $orden = DB::connection('galy')->transaction(function () use (
                $carritos,
                $cuponAplicado,
                $descuento,
                $direccionId,
                $direccionTexto,
                $firmaCarrito,
                $modalidad,
                $metodo,
                $reservations,
                $total,
                $usuarioId,
                $ajuste
            ) {
                // Bloquea el carrito antes de escribir nada: dos envíos del mismo
                // formulario no pueden crear dos pedidos con los mismos productos.
                $carritoVivo = \App\Models\Carrito::where('usuario_id', $usuarioId)->lockForUpdate()->get()
                    ->map(fn ($item) => ((int) $item->producto_id).':'.((int) $item->cantidad))
                    ->sort()
                    ->values()
                    ->implode('|');

                if ($carritoVivo !== $firmaCarrito) {
                    return null;
                }

                $orden = new Orden;
                $orden->usuario_id = $usuarioId;
                $orden->direccion_id = $direccionId;
                $orden->total = $total;
                $orden->cupon = $cuponAplicado;
                $orden->descuento = $descuento > 0 ? $descuento : null;
                $orden->divisa = $ajuste?->divisa ?? 'PEN';
                $orden->estado_pago = 'Pendiente';
                $orden->estado_orden = 'En proceso';
                $orden->direccion_envio = $direccionTexto;
                $orden->modalidad_entrega = $modalidad;
                $orden->metodo_pago = $metodo;
                $orden->save();

                if ($metodo !== 'tarjeta') {
                    PagoManual::create([
                        'pedido_id' => $orden->id,
                        'usuario_id' => $usuarioId,
                        'metodo_pago' => $metodo,
                        'estado' => 'pendiente',
                    ]);
                }

                $historial = new \App\Models\PedidoHistorial;
                $historial->orden_id = $orden->id;
                $historial->estado_anterior = null;
                $historial->estado_nuevo = 'En proceso';
                $historial->fecha = now()->toDateString();
                $historial->hora = now()->toTimeString();
                $historial->nota = ($metodo === 'tarjeta'
                    ? 'Pedido creado. Esperando el pago con tarjeta en Mercado Pago.'
                    : 'Pedido creado. Pago '.$metodo.' pendiente de transferencia y comprobante.')
                    .($cuponAplicado !== null
                        ? ' Cupón '.$cuponAplicado.': '.Ajuste::formatear_precio($descuento).' de descuento.'
                        : '');
                $historial->usuario_id = null;
                $historial->save();

                foreach ($carritos as $item) {
                    $detalle = new \App\Models\DetalleOrden;
                    $detalle->orden_id = $orden->id;
                    $detalle->producto_id = $item->producto_id;
                    $detalle->cantidad = $item->cantidad;
                    $detalle->precio = $item->producto->en_oferta && $item->producto->precio_oferta > 0
                        ? $item->producto->precio_oferta
                        : $item->producto->precio_venta;
                    $detalle->save();

                    $reservations->consumeForOrder(
                        $usuarioId,
                        (int) $item->producto_id,
                        (int) $item->cantidad,
                        (int) $orden->id
                    );
                    $item->delete();
                }

                return $orden;
            });
        } catch (\Throwable $exception) {
            report($exception);

            if ($intento) {
                $intento->estado = IntentoPagoMercadoPago::ESTADO_FALLIDO;
                $intento->save();
            }

            return redirect()->route('web.carrito.index')
                ->with('mensaje', 'No se pudo registrar el pedido. Intenta nuevamente o contacta a la tienda.')
                ->with('icono', 'error');
        }

        if ($orden === null) {
            if ($intento) {
                $intento->estado = IntentoPagoMercadoPago::ESTADO_FALLIDO;
                $intento->save();
            }

            return redirect()->route('web.carrito.index')
                ->with('mensaje', 'Tu carrito cambió mientras se registraba el pedido, así que no se creó ninguna compra duplicada. Revisa el carrito e inténtalo de nuevo.')
                ->with('icono', 'warning');
        }

        // El cupón ya quedó escrito en el pedido: si la sesión lo siguiera
        // guardando, el siguiente carrito aparecería descontado sin que nadie
        // lo pidiera.
        session()->forget('carrito_cupon');

        if ($intento) {
            $intento->pedido_id = $orden->id;
            $intento->save();

            $this->notificarPedidoRegistrado($orden);

            return redirect()->away($intento->checkout_url);
        }

        $this->notificarPedidoRegistrado($orden);

        return redirect()->route('web.pedidos.confirmacion', $orden->id)
            ->with('mensaje', 'Pedido creado. Realiza la transferencia y envía el comprobante para que la tienda revise el pago.')
            ->with('icono', 'success');
    }

    /**
     * El correo es una comodidad, no parte del cobro: si el servidor de correo
     * falla, el pedido ya quedó registrado y el cliente igual lo ve en la web.
     */
    private function notificarPedidoRegistrado(Orden $orden): void
    {
        $correo = (string) (auth()->user()->email ?? $orden->cliente_email);

        if ($correo === '') {
            return;
        }

        try {
            Mail::to($correo)->send(new CompraConfirmada($orden));
        } catch (\Throwable $exception) {
            Log::error('No se pudo enviar el correo de confirmación del pedido.', [
                'pedido_id' => $orden->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function enviarComprobantePagoManual(Request $request, $id)
    {
        $request->validate([
            'comprobante' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $usuarioId = auth()->user()->resolveGalyUserId();
        if ($usuarioId === null) {
            return redirect()->route('web.carrito.index')
                ->with('mensaje', 'No se encontró una cuenta GALY vinculada a este correo. Inicia sesión con tu cuenta GALY para consultar tus pedidos.')
                ->with('icono', 'warning');
        }

        $orden = Orden::where('usuario_id', $usuarioId)->findOrFail($id);
        $pago = PagoManual::where('pedido_id', $orden->id)->where('usuario_id', $usuarioId)->firstOrFail();

        if ($pago->estado === 'confirmado' || in_array($orden->estado_pago, ['Pagado', 'COMPLETED', 'approved'], true)) {
            return redirect()->route('web.pedidos.confirmacion', $orden->id)
                ->with('mensaje', 'El pago de este pedido ya fue confirmado.')
                ->with('icono', 'info');
        }

        $previousPath = $pago->comprobante_path;
        $comprobantePath = $request->file('comprobante')->store('comprobantes-pago', 'local');
        if (! $comprobantePath) {
            throw new \RuntimeException('No se pudo guardar el comprobante de pago.');
        }

        $pago->comprobante_path = $comprobantePath;
        $pago->estado = 'comprobante_enviado';
        $pago->comprobante_enviado_at = now();
        $pago->save();

        if ($previousPath && Storage::disk('local')->exists($previousPath)) {
            Storage::disk('local')->delete($previousPath);
        }

        return redirect()->route('web.pedidos.confirmacion', $orden->id)
            ->with('mensaje', 'Comprobante enviado. El pedido seguirá pendiente hasta que la tienda confirme la transferencia.')
            ->with('icono', 'success');
    }

    public function verMiComprobantePagoManual($id)
    {
        $usuarioId = auth()->user()->resolveGalyUserId();
        abort_if($usuarioId === null, 403);

        $orden = Orden::where('usuario_id', $usuarioId)->findOrFail($id);
        $pago = PagoManual::where('pedido_id', $orden->id)
            ->where('usuario_id', $usuarioId)
            ->firstOrFail();

        abort_unless($pago->comprobante_path, 404);
        abort_unless(Storage::disk('local')->exists($pago->comprobante_path), 404, 'El archivo del comprobante ya no está disponible.');

        return Storage::disk('local')->response($pago->comprobante_path);
    }

    public function verComprobantePagoManual($id)
    {
        $pago = PagoManual::where('pedido_id', $id)->firstOrFail();
        abort_unless($pago->comprobante_path, 404);
        abort_unless(Storage::disk('local')->exists($pago->comprobante_path), 404, 'El archivo del comprobante ya no está disponible.');

        return Storage::disk('local')->response($pago->comprobante_path);
    }

    /**
     * Mark the payment status as Pagado from the admin panel.
     */
    public function marcarPagado(Request $request, $id)
    {
        $ordenInicial = Orden::findOrFail($id);
        $metodo = strtolower((string) $ordenInicial->metodo_pago);
        $esPagoManual = in_array($metodo, ['yape', 'plin', 'bcp'], true);
        $esTarjeta = $metodo === 'tarjeta';

        if ($esPagoManual && ! $request->boolean('verificado_en_app')) {
            return redirect()->back()
                ->with('mensaje', 'Antes de confirmar, verifica en la app de Yape o Plin que el dinero haya llegado.')
                ->with('icono', 'warning');
        }

        if ($esTarjeta && ! $request->boolean('verificado_en_mercadopago')) {
            return redirect()->back()
                ->with('mensaje', 'Mercado Pago confirma los pagos con tarjeta solo. Marca la casilla únicamente si ya ves el cobro aprobado en tu cuenta.')
                ->with('icono', 'warning');
        }

        $resultado = DB::connection('galy')->transaction(function () use ($id, $esPagoManual, $esTarjeta): string {
            $orden = Orden::query()->lockForUpdate()->findOrFail($id);
            $pagoManual = PagoManual::query()
                ->where('pedido_id', $orden->id)
                ->lockForUpdate()
                ->first();

            if (in_array($orden->estado_pago, ['Pagado', 'COMPLETED', 'approved'], true)) {
                return 'already_paid';
            }

            if ($esPagoManual) {
                if ($pagoManual
                    && $pagoManual->comprobante_path
                    && ! Storage::disk('local')->exists($pagoManual->comprobante_path)) {
                    return 'receipt_missing';
                }

                if ($pagoManual?->comprobante_path
                    && $pagoManual->estado !== 'comprobante_enviado') {
                    return 'receipt_missing';
                }
            }

            $estadoAnterior = $orden->estado_pago;
            $orden->estado_pago = 'Pagado';
            $orden->save();

            if ($pagoManual) {
                $pagoManual->estado = 'confirmado';
                $pagoManual->confirmado_at = now();
                $pagoManual->confirmado_por = \Illuminate\Support\Facades\Auth::id();
                $pagoManual->save();
            }

            $historial = new \App\Models\PedidoHistorial();
            $historial->orden_id = $orden->id;
            $historial->estado_anterior = $estadoAnterior;
            $historial->estado_nuevo = $orden->estado_orden;
            $historial->fecha = now()->toDateString();
            $historial->hora = now()->toTimeString();
            $historial->nota = match (true) {
                $esPagoManual => 'El administrador verificó en la app de pago el abono y confirmó el comprobante de '.ucfirst($orden->metodo_pago).'.',
                $esTarjeta => 'El administrador confirmó el cobro con tarjeta revisándolo en su cuenta de Mercado Pago.',
                default => 'El administrador marcó el pago como PAGADO (Pago recibido en tienda).',
            };
            $historial->usuario_id = \Illuminate\Support\Facades\Auth::user()->galy_user_id;
            $historial->save();

            return 'paid';
        });

        if ($resultado === 'already_paid') {
            return redirect()->back()
                ->with('mensaje', 'El pedido ya está pagado.')
                ->with('icono', 'warning');
        }

        if ($resultado === 'receipt_missing') {
            return redirect()->back()
                ->with('mensaje', 'No se puede confirmar el pago: el comprobante no está disponible para revisión.')
                ->with('icono', 'warning');
        }

        return redirect()->back()
            ->with('mensaje', 'El estado del pago se ha actualizado a: Pagado')
            ->with('icono', 'success');
    }
}
