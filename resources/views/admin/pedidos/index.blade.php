@extends('layouts.admin')

@section('title', 'Pedidos · ' . ($ajuste->nombre ?? 'TECTRON'))

@section('content')
    @php
        $coleccionPedidos = $pedidos->getCollection();
        $enProceso = $coleccionPedidos->where('estado_orden', 'En proceso')->count();
        $entregados = $coleccionPedidos->filter(fn ($p) => in_array($p->estado_orden, ['Entregado', 'Listo para su recojo'], true))->count();
    @endphp

    <div class="ga-page-head">
        <div class="ga-page-head-main">
            <div class="ga-eyebrow">Operación de la tienda</div>
            <h1 class="ga-page-title">Pedidos</h1>
            <p class="ga-page-sub">Revisa cada pedido, su forma de entrega y el estado del pago. Desde la ficha puedes avanzar el estado y registrar el pago manual.</p>
        </div>
        <div class="ga-head-actions">
            <a class="btn-ga-ghost" href="{{ url('/admin/pagos') }}">
                <i class="bi bi-credit-card" aria-hidden="true"></i> Pagos
            </a>
            <span class="ga-count-pill">
                <i class="bi bi-receipt" aria-hidden="true"></i>
                <strong>{{ number_format($pedidos->total()) }}</strong> pedidos
            </span>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="ga-kpi">
                <div class="ga-kpi-top">
                    <div>
                        <p class="ga-kpi-label">Total histórico</p>
                        <p class="ga-kpi-value">{{ number_format($pedidos->total()) }}</p>
                    </div>
                    <span class="ga-kpi-icon"><i class="bi bi-receipt-cutoff" aria-hidden="true"></i></span>
                </div>
                <span class="ga-kpi-foot"><i class="bi bi-clock-history" aria-hidden="true"></i> Todos los pedidos</span>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="ga-kpi">
                <div class="ga-kpi-top">
                    <div>
                        <p class="ga-kpi-label">En esta página</p>
                        <p class="ga-kpi-value">{{ number_format($pedidos->count()) }}</p>
                    </div>
                    <span class="ga-kpi-icon is-info"><i class="bi bi-layers" aria-hidden="true"></i></span>
                </div>
                <span class="ga-kpi-foot"><i class="bi bi-eye" aria-hidden="true"></i> Visibles ahora</span>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="ga-kpi">
                <div class="ga-kpi-top">
                    <div>
                        <p class="ga-kpi-label">En proceso</p>
                        <p class="ga-kpi-value">{{ number_format($enProceso) }}</p>
                    </div>
                    <span class="ga-kpi-icon is-warning"><i class="bi bi-hourglass-split" aria-hidden="true"></i></span>
                </div>
                <span class="ga-kpi-foot"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Por atender</span>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="ga-kpi">
                <div class="ga-kpi-top">
                    <div>
                        <p class="ga-kpi-label">Entregados</p>
                        <p class="ga-kpi-value">{{ number_format($entregados) }}</p>
                    </div>
                    <span class="ga-kpi-icon is-success"><i class="bi bi-bag-check-fill" aria-hidden="true"></i></span>
                </div>
                <span class="ga-kpi-foot"><i class="bi bi-check2-circle" aria-hidden="true"></i> O listos para recojo</span>
            </div>
        </div>
    </div>

    <div class="ga-surface ga-table-card">
        <div class="ga-card-head">
            <div>
                <h2>Pedidos registrados</h2>
                <p>Más recientes primero</p>
            </div>
            @if ($pedidos->hasPages())
                <span class="badge bg-secondary">
                    Página {{ $pedidos->currentPage() }} de {{ $pedidos->lastPage() }}
                </span>
            @endif
        </div>

        <div class="card-body">
            <form action="{{ url('/admin/pedidos') }}" method="GET" class="ga-toolbar mb-3" role="search">
                <div class="ga-search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <label class="visually-hidden" for="buscar-pedidos">Buscar pedidos</label>
                    <input type="search" id="buscar-pedidos" name="buscar" class="form-control"
                        placeholder="Buscar por cliente, correo o producto" value="{{ $_REQUEST['buscar'] ?? '' }}">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search" aria-hidden="true"></i> Buscar
                </button>
                @if (isset($_REQUEST['buscar']))
                    <a href="{{ url('/admin/pedidos') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg" aria-hidden="true"></i> Limpiar
                    </a>
                @endif
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nro</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Entrega</th>
                            <th>Pago</th>
                            <th>Estado</th>
                            <th>Dirección</th>
                            <th>Productos</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $nro = ($pedidos->currentPage() - 1) * $pedidos->perPage() + 1;
                        @endphp
                        @forelse ($pedidos as $pedido)
                            @php($cliente = $pedido->usuario ?? $usuariosGaly->get($pedido->usuario_id))
                            @php($nombreCliente = $pedido->usuario?->name ?? $cliente?->nombre ?? 'Cliente no vinculado')
                            <tr>
                                <td class="ga-muted">{{ $nro++ }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="ga-avatar ga-avatar-sm">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($nombreCliente, 0, 1)) }}</span>
                                        <span>
                                            <strong class="d-block small">{{ $nombreCliente }}</strong>
                                            <small class="ga-muted">
                                                {{ $pedido->usuario?->email ?? $cliente?->email ?? 'ID GALY: ' . ($pedido->usuario_id ?? 'no disponible') }}
                                            </small>
                                        </span>
                                    </div>
                                </td>
                                <td class="ga-nowrap fw-bold">{{ $pedido->divisa . ' ' . number_format($pedido->total, 2) }}</td>
                                <td>
                                    @if ($pedido->modalidad_entrega === 'recojo')
                                        <span class="badge bg-success"><i class="bi bi-shop" aria-hidden="true"></i> Recojo</span>
                                    @else
                                        <span class="badge bg-primary"><i class="bi bi-truck" aria-hidden="true"></i> Envío</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="ga-muted d-block mb-1">{{ $pedido->getMetodoPagoTexto() }}</small>
                                    <span class="badge bg-{{ $pedido->getEstadoPagoBadgeColor() }}">
                                        {{ $pedido->getEstadoPagoTexto() }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $pedido->getBadgeColor() }}">
                                        {{ $pedido->estado_orden }}
                                    </span>
                                </td>
                                <td>
                                    <small class="d-block text-truncate" style="max-width: 180px;" title="{{ $pedido->direccion_envio }}">
                                        {{ $pedido->direccion_envio ?: '—' }}
                                    </small>
                                </td>
                                <td>
                                    <ul class="ga-list-reset d-grid gap-1">
                                        @forelse ($pedido->detalles as $detalle)
                                            <li>
                                                <small class="ga-muted">
                                                    {{ $detalle->producto?->nombre ?? 'Producto no disponible' }}
                                                    <span class="badge bg-secondary">x{{ $detalle->cantidad }}</span>
                                                </small>
                                            </li>
                                        @empty
                                            <li><small class="ga-muted">Sin productos</small></li>
                                        @endforelse
                                    </ul>
                                </td>
                                <td class="text-end">
                                    <a href="{{ url('/admin/pedido/' . $pedido->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-gear" aria-hidden="true"></i> Gestionar
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="ga-empty">
                                        <span class="ga-empty-icon"><i class="bi bi-receipt" aria-hidden="true"></i></span>
                                        <strong>{{ isset($_REQUEST['buscar']) ? 'No encontramos pedidos con ese criterio' : 'Todavía no hay pedidos' }}</strong>
                                        <p>{{ isset($_REQUEST['buscar']) ? 'Prueba con otro cliente, correo o producto.' : 'Cuando un cliente compre en la tienda, el pedido aparecerá aquí.' }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($pedidos->hasPages())
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4 pt-3 border-top" style="border-color: var(--ga-line) !important;">
                    <div class="ga-muted small">
                        <i class="bi bi-list-ol" aria-hidden="true"></i>
                        Mostrando {{ $pedidos->firstItem() }} a {{ $pedidos->lastItem() }} de
                        {{ $pedidos->total() }} registros
                    </div>
                    <div>
                        {{ $pedidos->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
