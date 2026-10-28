@extends('layouts.admin')

@section('content')
    <div class="payment-register__header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <span class="payment-register__eyebrow"><i class="bi bi-shield-check me-1" aria-hidden="true"></i> Control de pagos</span>
            <h1 class="mb-1">Registro de pagos</h1>
            <p class="text-muted mb-0">Los pedidos manuales de la app y la web aparecen aquí hasta que la tienda verifique el abono. Los pagos con tarjeta los confirma Mercado Pago automáticamente.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ request()->fullUrl() }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i> Actualizar pagos
            </a>
            <a href="{{ route('admin.pedidos.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-card-checklist me-1"></i> Ver pedidos
            </a>
        </div>
    </div>

    @if ($enlacePublico)
        @if ($enlacePublico['ok'])
            <div class="small text-muted mb-4">
                <i class="bi bi-link-45deg me-1" aria-hidden="true"></i>
                Mercado Pago devuelve al cliente a <code>{{ $enlacePublico['url'] }}</code> — {{ $enlacePublico['detalle'] }}
            </div>
        @else
            <div class="alert alert-warning" role="alert">
                <strong><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i> El enlace de retorno está caído.</strong>
                El cliente que paga con tarjeta vuelve a una dirección que no abre, así que un rechazo se ve como un error de la tienda.
                No se perdió ningún cobro: Mercado Pago no cobra los intentos rechazados.
                <div class="small mt-1">Dirección configurada: <code>{{ $enlacePublico['url'] }}</code> · {{ $enlacePublico['detalle'] }}</div>
                <div class="small">Arreglo: doble clic en <code>tunel-galy.bat</code>, que levanta el túnel y actualiza el <code>.env</code> solo.</div>
            </div>
        @endif
    @endif

    <div class="row g-3 mb-4" aria-label="Resumen de pagos">
        @foreach ([
            ['key' => 'por_revisar', 'label' => 'Comprobantes por revisar', 'icon' => 'bi-receipt-cutoff', 'color' => 'warning', 'description' => 'Requieren revisar el abono en la app.'],
            ['key' => 'esperando', 'label' => 'Esperando verificación', 'icon' => 'bi-hourglass-split', 'color' => 'secondary', 'description' => 'Pedido registrado; falta verificar el abono.'],
            ['key' => 'confirmado', 'label' => 'Pagos confirmados', 'icon' => 'bi-shield-check', 'color' => 'success', 'description' => 'Transferencias revisadas por la tienda.'],
            ['key' => 'tarjetas', 'label' => 'Pagos con tarjeta', 'icon' => 'bi-credit-card', 'color' => 'info', 'description' => 'Mercado Pago los confirma solo; falta pagar o verificar.'],
            ['key' => 'anteriores', 'label' => 'Registros anteriores', 'icon' => 'bi-archive', 'color' => 'primary', 'description' => 'Pedidos sin comprobante manual asociado.'],
        ] as $summaryCard)
            <div class="col-12 col-sm-6 col-xl-3">
                <a href="{{ route('admin.pagos.index', ['revision' => $summaryCard['key']]) }}"
                    class="card payment-register__summary h-100 shadow-sm border-0 text-decoration-none {{ $revision === $summaryCard['key'] ? 'is-active border-start border-4 border-'.$summaryCard['color'] : '' }}"
                    aria-current="{{ $revision === $summaryCard['key'] ? 'page' : 'false' }}"
                    aria-label="{{ $summaryCard['label'] }}: {{ number_format($resumenPagos[$summaryCard['key']]) }}. {{ $summaryCard['description'] }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <small class="text-muted">{{ $summaryCard['label'] }}</small>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fs-3 fw-bold text-body">{{ number_format($resumenPagos[$summaryCard['key']]) }}</span>
                                    @if ($revision === $summaryCard['key'])
                                        <span class="badge bg-{{ $summaryCard['color'] }} bg-opacity-10 text-{{ $summaryCard['color'] }}">Viendo</span>
                                    @endif
                                </div>
                            </div>
                            <span class="payment-register__summary-icon text-{{ $summaryCard['color'] }}" aria-hidden="true">
                                <i class="bi {{ $summaryCard['icon'] }} fs-5"></i>
                            </span>
                        </div>
                        <small class="text-muted">{{ $summaryCard['description'] }}</small>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="card payment-register__panel shadow-sm border-0">
        <div class="card-body p-3 p-lg-4">
            <div class="payment-register__section-heading d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h2 class="h5 mb-1">Buscar y filtrar</h2>
                    <p class="small text-muted mb-0">Combina los filtros para encontrar el pedido que necesitas.</p>
                </div>
                @if (request()->hasAny(['buscar', 'estado', 'metodo']) || $revision !== 'por_revisar')
                    <span class="badge rounded-pill text-bg-light border">
                        <i class="bi bi-funnel me-1" aria-hidden="true"></i>
                        Vista: {{ [
                            'por_revisar' => 'Por revisar',
                            'esperando' => 'Esperando verificación',
                            'confirmado' => 'Confirmados',
                            'tarjetas' => 'Pagos con tarjeta',
                            'anteriores' => 'Anteriores',
                            'todos' => 'Todos',
                        ][$revision] ?? 'Por revisar' }}
                    </span>
                @endif
            </div>

            <form action="{{ route('admin.pagos.index') }}" method="GET" class="payment-register__filters row g-3 align-items-end mb-4">
                <div class="col-12 col-md-6 col-xl-3">
                    <label for="buscar" class="form-label">Pedido o referencia</label>
                    <div class="input-group">
                        <span class="input-group-text" aria-hidden="true"><i class="bi bi-search"></i></span>
                        <input id="buscar" type="search" name="buscar" class="form-control"
                            value="{{ request('buscar') }}" placeholder="N.º de pedido o transacción"
                            maxlength="100" autocomplete="off">
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-2">
                    <label for="estado" class="form-label">Estado del pago</label>
                    <select id="estado" name="estado" class="form-select">
                        <option value="">Todos los estados</option>
                        @foreach (['pendiente' => 'Pendiente', 'pagado' => 'Pagado', 'reembolsado' => 'Reembolsado', 'cancelado' => 'Cancelado'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('estado') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-xl-2">
                    <label for="metodo" class="form-label">Método de pago</label>
                    <select id="metodo" name="metodo" class="form-select">
                        <option value="">Todos los métodos</option>
                        @foreach (['yape' => 'Yape', 'bcp' => 'BCP', 'plin' => 'Plin', 'tarjeta' => 'Tarjeta'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('metodo') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-8 col-xl-3">
                    <label for="revision" class="form-label">Revisión del comprobante</label>
                    <select id="revision" name="revision" class="form-select">
                        @foreach ([
                            'por_revisar' => 'Por revisar ahora',
                            'esperando' => 'Esperando verificación',
                            'confirmado' => 'Pago confirmado',
                            'tarjetas' => 'Pagos con tarjeta',
                            'anteriores' => 'Registros anteriores',
                            'todos' => 'Todos los métodos y pedidos',
                        ] as $value => $label)
                            <option value="{{ $value }}" @selected($revision === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-4 col-xl-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-funnel me-1" aria-hidden="true"></i> Aplicar
                    </button>
                    <a href="{{ route('admin.pagos.index') }}" class="btn btn-outline-secondary"
                        aria-label="Limpiar filtros y volver a pagos esperando verificación" title="Limpiar filtros">
                        <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                    </a>
                </div>
            </form>

            @if ($pagos->isEmpty())
                <div class="payment-register__empty text-center text-muted py-5" role="status">
                    <span class="payment-register__empty-icon d-inline-grid place-items-center mb-3">
                        <i class="bi {{ $revision === 'por_revisar' ? 'bi-check2-circle text-success' : 'bi-search' }} display-6" aria-hidden="true"></i>
                    </span>
                    <p class="fw-semibold mb-1">
                        {{ $revision === 'por_revisar' ? 'No hay comprobantes que revisar.' : 'No se encontraron pagos con esos filtros.' }}
                    </p>
                    <small>
                        {{ $revision === 'por_revisar' ? 'Los pedidos que esperan verificación aparecen en la sección «Esperando verificación».' : 'Prueba otro filtro o consulta todos los pedidos.' }}
                    </small>
                    @if ($revision !== 'por_revisar' || request()->hasAny(['buscar', 'estado', 'metodo']))
                        <div class="mt-3">
                            <a href="{{ route('admin.pagos.index') }}" class="btn btn-sm btn-outline-primary">Volver a comprobantes por revisar</a>
                        </div>
                    @endif
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle payment-register__table">
                        <caption class="visually-hidden">
                            Pagos encontrados. Los comprobantes deben verificarse en la aplicación del proveedor antes de confirmar.
                        </caption>
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Pedido</th>
                                <th scope="col">Fecha</th>
                                <th scope="col">Cliente</th>
                                <th scope="col">Método</th>
                                <th scope="col">Referencia</th>
                                <th scope="col" class="text-end">Importe</th>
                                <th scope="col">Estado de pago</th>
                                <th scope="col">Revisión</th>
                                <th scope="col" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pagos as $pago)
                                @php($cliente = $pago->usuario ?? $usuariosGalyPorId->get($pago->usuario_id) ?? $usuariosGaly->get(strtolower((string) $pago->cliente_email)))
                                @php($esTarjeta = strtolower((string) $pago->metodo_pago) === 'tarjeta')
                                @php($intento = $esTarjeta ? $pago->intentosPago->first() : null)
                                @php($intentoRevision = $esTarjeta
                                    && ! in_array($pago->estado_pago, ['Pagado', 'COMPLETED', 'approved'], true)
                                    ? $pago->intentosPago->first(fn ($item) => $item->requiereRevisionManual())
                                    : null)
                                @php($intentoReversion = $esTarjeta
                                    && in_array($pago->estado_pago, ['Pagado', 'COMPLETED', 'approved'], true)
                                    ? $pago->intentosPago->first(fn ($item) => $item->cobroRevertido())
                                    : null)
                                <tr class="{{ $pago->pagoManual?->comprobante_path ? 'payment-register__row--receipt' : '' }}">
                                    <td class="fw-semibold text-nowrap">#{{ $pago->id }}</td>
                                    <td class="text-nowrap">
                                        @if ($pago->fecha_registro)
                                            <time datetime="{{ $pago->fecha_registro->toIso8601String() }}">{{ $pago->fecha_registro->format('d/m/Y') }}</time>
                                            <small class="d-block text-muted">{{ $pago->fecha_registro->format('H:i') }}</small>
                                        @else
                                            <span class="text-muted">Fecha no disponible</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="fw-semibold d-block">{{ $pago->usuario?->name ?? $cliente?->nombre ?? $pago->cliente_nombre ?? ($pago->cliente_email ? 'Cliente de la app' : 'Cliente no vinculado') }}</span>
                                        <small class="text-muted text-break">{{ $pago->usuario?->email ?? $cliente?->email ?? $pago->cliente_email ?? 'Sin correo asociado' }}</small>
                                    </td>
                                    <td><span class="badge bg-{{ in_array(strtolower($pago->metodo_pago), ['yape', 'bcp', 'tarjeta'], true) ? 'primary' : (strtolower($pago->metodo_pago) === 'plin' ? 'success' : 'secondary') }}">{{ $pago->getMetodoPagoTexto() }}</span></td>
                                    <td>
                                        <small class="text-muted text-break">
                                            @if ($esTarjeta)
                                                {{ $intento?->mercadopago_payment_id ?: 'Sin referencia de Mercado Pago' }}
                                            @else
                                                {{ $pago->pagoManual?->numero_operacion ?: ($pago->transaccion_id ?: 'Sin referencia') }}
                                            @endif
                                        </small>
                                    </td>
                                    <td class="text-end fw-semibold text-nowrap">{{ $pago->divisa }} {{ number_format((float) $pago->total, 2) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $pago->getEstadoPagoBadgeColor() }}">
                                            {{ $pago->getEstadoPagoTexto() }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($esTarjeta)
                                            <span class="badge bg-{{ $intento?->getBadgeColor() ?? 'light text-secondary border' }} mb-1">{{ $intento?->getEstadoTexto() ?? 'Sin intento abierto' }}</span>
                                            @if ($intentoRevision)
                                                <span class="badge bg-danger mb-1">Cobro por verificar</span>
                                            @endif
                                            @if ($intentoReversion)
                                                <span class="badge bg-danger mb-1">Cobro revertido</span>
                                            @endif
                                            <small class="d-block text-muted mt-1">
                                                @if ($intentoRevision)
                                                    Mercado Pago reportó un cobro aprobado que no cuadra con el pedido: {{ $intentoRevision->detalle_estado_mercadopago ?: 'revisión manual' }}. Revísalo en tu cuenta de Mercado Pago antes de confirmarlo.
                                                @elseif ($intentoReversion)
                                                    Mercado Pago devolvió el cobro ({{ $intentoReversion->estado_mercadopago }}) de este pedido marcado como pagado. No lo envíes hasta haberlo confirmado en tu cuenta.
                                                @elseif ($intento && $intento->estado_mercadopago)
                                                    Mercado Pago: {{ $intento->estado_mercadopago }}{{ $intento->detalle_estado_mercadopago ? ' · '.$intento->detalle_estado_mercadopago : '' }}
                                                @elseif ($intento)
                                                    Mercado Pago confirma el pago solo; no hay comprobante que revisar.
                                                @else
                                                    El cliente no llegó a abrir la pasarela. Puede reintentar desde su pedido.
                                                @endif
                                                @if ($intento?->estaVigente())
                                                    <span class="d-block">Vence a las {{ $intento->expira_at->format('H:i') }} del {{ $intento->expira_at->format('d/m/Y') }}.</span>
                                                @endif
                                            </small>
                                        @elseif ($pago->pagoManual?->comprobante_path)
                                            <span class="badge bg-warning text-dark mb-1">Por revisar</span>
                                            @can('Procesar Pedido')
                                                <a href="{{ route('admin.pedidos.comprobante', $pago->id) }}"
                                                    class="btn btn-sm btn-primary d-block" target="_blank" rel="noopener noreferrer"
                                                    aria-label="Abrir comprobante del pedido #{{ $pago->id }} en una pestaña nueva">
                                                    <i class="bi bi-image me-1" aria-hidden="true"></i> Ver comprobante
                                                </a>
                                                <small class="d-block text-muted mt-1">Verifica el abono en la app antes de confirmar.</small>
                                            @else
                                                <span class="d-block small text-muted">Comprobante recibido</span>
                                            @endcan
                                        @elseif ($pago->pagoManual)
                                            <span class="badge bg-secondary">Esperando verificación</span>
                                            <small class="d-block text-muted mt-1">Pedido registrado; verifica el abono directamente en la app bancaria.</small>
                                        @else
                                            <span class="badge bg-light text-secondary border">Registro anterior</span>
                                            <small class="d-block text-muted mt-1">Sin comprobante asociado en este sistema.</small>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.pedidos.create', $pago->id) }}" class="btn btn-sm btn-outline-secondary text-nowrap">
                                            <i class="bi bi-eye me-1"></i> Ver pedido
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($pagos->hasPages())
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                        <small class="text-muted">
                            Mostrando {{ $pagos->firstItem() }} a {{ $pagos->lastItem() }} de {{ $pagos->total() }} pagos
                        </small>
                        {{ $pagos->links('pagination::bootstrap-4') }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    <style>
        .payment-register__eyebrow {
            display: inline-block;
            margin-bottom: .35rem;
            color: var(--bs-primary);
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .07em;
            text-transform: uppercase;
        }
        .payment-register__summary {
            color: inherit;
            border-radius: .85rem;
            transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
        }
        .payment-register__summary:hover {
            color: inherit;
            transform: translateY(-2px);
            box-shadow: 0 .75rem 1.5rem rgba(16, 24, 40, .1) !important;
        }
        .payment-register__summary:focus-visible {
            outline: 3px solid color-mix(in srgb, var(--bs-primary) 55%, transparent);
            outline-offset: 3px;
        }
        .payment-register__summary.is-active { box-shadow: 0 .35rem 1rem rgba(16, 24, 40, .08) !important; }
        .payment-register__summary-icon {
            display: inline-grid;
            width: 2.5rem;
            height: 2.5rem;
            place-items: center;
            border-radius: 50%;
            background: color-mix(in srgb, currentColor 10%, transparent);
        }
        .payment-register__panel { border-radius: .9rem; }
        .payment-register__filters {
            padding: 1rem;
            border: 1px solid var(--bs-border-color);
            border-radius: .75rem;
            background: color-mix(in srgb, var(--bs-tertiary-bg) 55%, transparent);
        }
        .payment-register__filters .form-label { font-size: .82rem; font-weight: 600; }
        .payment-register__filters .form-control,
        .payment-register__filters .form-select,
        .payment-register__filters .input-group-text { min-height: 2.7rem; }
        .payment-register__table { min-width: 900px; }
        .payment-register__table thead th {
            padding-top: .8rem;
            padding-bottom: .8rem;
            color: var(--bs-emphasis-color);
            font-size: .75rem;
            letter-spacing: .02em;
            white-space: nowrap;
        }
        .payment-register__table tbody td { padding-top: .75rem; padding-bottom: .75rem; }
        .payment-register__row--receipt { box-shadow: inset 3px 0 0 var(--bs-warning); }
        .payment-register__empty-icon {
            width: 4rem;
            height: 4rem;
            place-items: center;
            border-radius: 50%;
            background: var(--bs-tertiary-bg);
        }
        @media (max-width: 575.98px) {
            .payment-register__header .btn { width: 100%; }
            .payment-register__filters { padding: .8rem; }
        }
        @media (prefers-reduced-motion: reduce) {
            .payment-register__summary { transition: none; }
            .payment-register__summary:hover { transform: none; }
        }
    </style>
@endsection
