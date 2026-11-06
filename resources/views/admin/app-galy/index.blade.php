@extends('layouts.admin')

@section('content')
    <style>
        .app-galy-tile {
            border-color: var(--bs-border-color) !important;
            color: inherit;
        }

        .app-galy-tile:hover {
            border-color: var(--bs-primary) !important;
            background: var(--bs-primary-bg-subtle);
        }
    </style>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <div class="text-uppercase text-primary fw-semibold small mb-1">App GALY</div>
            <h1 class="mb-1">Resumen de la aplicación</h1>
            <p class="text-muted mb-0">
                Ventas, clientes, stock y utilidad compartidos con la app Android. Los cambios que hagas aquí se
                reflejan en la app y en la tienda web.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.app-galy.clientes') }}" class="btn btn-outline-primary">
                <i class="bi bi-people" aria-hidden="true"></i> Clientes app
            </a>
            <a href="{{ route('admin.app-galy.pedidos') }}" class="btn btn-outline-primary">
                <i class="bi bi-receipt" aria-hidden="true"></i> Pedidos app
            </a>
            <a href="{{ route('admin.app-galy.administradores') }}" class="btn btn-outline-primary">
                <i class="bi bi-shield-lock" aria-hidden="true"></i> Administradores
            </a>
            <a href="{{ route('admin.app-galy.notificaciones') }}" class="btn btn-outline-primary">
                <i class="bi bi-bell" aria-hidden="true"></i> Notificaciones
            </a>
            <a href="{{ route('admin.app-galy.comentarios') }}" class="btn btn-outline-primary">
                <i class="bi bi-chat-left-text" aria-hidden="true"></i> Comentarios
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4" id="funciones-app">
        <div class="card-header bg-transparent d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="h5 mb-1">Funciones del administrador de la app</h2>
                <p class="text-muted small mb-0">
                    Lo mismo que ves en el panel de la app Android, pero desde la tienda web. Los datos son los
                    mismos: un cambio aquí aparece al instante en la app.
                </p>
            </div>
            <span class="badge bg-primary-subtle text-primary">{{ count($funciones) }} funciones</span>
        </div>
        <div class="card-body">
            <div class="row g-2">
                @foreach ($funciones as $funcion)
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <a href="{{ route($funcion['ruta']) }}"
                            class="d-flex align-items-center gap-2 border rounded-3 p-3 h-100 text-decoration-none app-galy-tile">
                            <i class="bi {{ $funcion['icono'] }} fs-4 text-primary flex-shrink-0" aria-hidden="true"></i>
                            <span class="flex-grow-1">
                                <strong class="d-block">{{ $funcion['titulo'] }}</strong>
                                <small class="text-muted d-block">{{ $funcion['descripcion'] }}</small>
                            </span>
                            @if ($funcion['contador'] !== null)
                                <span class="badge bg-secondary-subtle text-secondary flex-shrink-0">
                                    {{ number_format($funcion['contador']) }}
                                </span>
                            @endif
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted text-uppercase small mb-1">Ventas totales</p>
                            <h2 class="h4 mb-0">S/ {{ number_format($ventas, 2) }}</h2>
                        </div>
                        <span class="badge bg-success-subtle text-success rounded-pill p-2">
                            <i class="bi bi-graph-up-arrow" aria-hidden="true"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted text-uppercase small mb-1">Pedidos</p>
                            <h2 class="h4 mb-0">{{ number_format($pedidosTotal) }}</h2>
                            <small class="text-muted">{{ number_format($pedidosMes) }} este mes</small>
                        </div>
                        <span class="badge bg-primary-subtle text-primary rounded-pill p-2">
                            <i class="bi bi-receipt" aria-hidden="true"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted text-uppercase small mb-1">Clientes de la app</p>
                            <h2 class="h4 mb-0">{{ number_format($clientesTotal) }}</h2>
                        </div>
                        <span class="badge bg-info-subtle text-info rounded-pill p-2">
                            <i class="bi bi-people" aria-hidden="true"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted text-uppercase small mb-1">Productos</p>
                            <h2 class="h4 mb-0">{{ number_format($productosTotal) }}</h2>
                            <small class="{{ $stockCritico > 0 ? 'text-danger' : 'text-muted' }}">
                                {{ number_format($stockCritico) }} con stock bajo
                            </small>
                        </div>
                        <span class="badge bg-warning-subtle text-warning rounded-pill p-2">
                            <i class="bi bi-box-seam" aria-hidden="true"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h2 class="h5 mb-0">Meta de ventas del mes</h2>
                            <p class="text-muted mb-0 small">Ventas registradas este mes frente a la meta configurada.</p>
                        </div>
                        <span class="badge bg-primary-subtle text-primary fs-6">{{ $porcentajeMeta }}%</span>
                    </div>
                    <div class="progress mb-2" role="progressbar" aria-label="Avance de la meta mensual"
                        aria-valuenow="{{ $porcentajeMeta }}" aria-valuemin="0" aria-valuemax="100" style="height: 12px;">
                        <div class="progress-bar {{ $porcentajeMeta >= 100 ? 'bg-success' : '' }}"
                            style="width: {{ min(100, $porcentajeMeta) }}%;"></div>
                    </div>
                    <div class="d-flex justify-content-between small text-muted">
                        <span>S/ {{ number_format($ventasMes, 2) }} vendidos</span>
                        <span>Meta: S/ {{ number_format($metaMensual, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase small mb-1">Utilidad potencial del inventario</p>
                    <h2 class="h4 mb-1">S/ {{ number_format($utilidadPotencial, 2) }}</h2>
                    <p class="text-muted small mb-0">
                        Costo del inventario: S/ {{ number_format($inventarioCosto, 2) }}
                    </p>
                    <hr>
                    <div class="d-flex justify-content-between small">
                        <span class="text-muted">Comentarios de clientes</span>
                        <strong>{{ number_format($comentariosTotal) }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($serieVentas !== [])
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h2 class="h5 mb-1">Ventas de los últimos 7 días</h2>
                    <p class="text-muted small mb-0">Pedidos pagados de la app y de la web, sin contar cancelados.</p>
                </div>
                <a href="{{ route('admin.app-galy.pedidos') }}" class="small">Ver pedidos de la app</a>
            </div>
            <div class="card-body">
                <div id="galy-ventas-7dias" role="img" aria-label="Gráfico de ventas de los últimos siete días"></div>
            </div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent">
                    <h2 class="h5 mb-0">Productos más vendidos</h2>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="text-end">Vendidos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topProductos as $producto)
                                <tr>
                                    <td>{{ $producto->nombre }}</td>
                                    <td class="text-end">{{ number_format((int) $producto->total_vendido) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-4">Todavía no hay ventas registradas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Stock crítico</h2>
                    <a href="{{ route('admin.productos.index') }}" class="small">Administrar productos</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="text-end">Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($productosCriticos as $producto)
                                <tr>
                                    <td>{{ $producto->nombre }}</td>
                                    <td class="text-end">
                                        <span class="badge {{ (int) $producto->stock <= 0 ? 'bg-danger' : 'bg-warning text-dark' }}">
                                            {{ (int) $producto->stock }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-4">No hay productos con stock bajo.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent">
                    <h2 class="h5 mb-0">Últimos pedidos de la app</h2>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Pedido</th>
                                <th>Estado</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ultimosPedidos as $pedido)
                                <tr>
                                    <td>#{{ $pedido->id }}</td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            {{ $pedido->estado ?: $pedido->estado_orden }}
                                        </span>
                                    </td>
                                    <td class="text-end">S/ {{ number_format((float) $pedido->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">No hay pedidos todavía.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent">
                    <h2 class="h5 mb-0">Ventas por distrito</h2>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Distrito</th>
                                <th class="text-end">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ventasDistrito as $distrito)
                                <tr>
                                    <td>{{ $distrito['label'] }}</td>
                                    <td class="text-end">S/ {{ number_format($distrito['monto'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-4">Sin datos por distrito.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if ($serieVentas !== [])
        <script>
            (function () {
                var datos = @json($serieVentas);
                var nodo = document.querySelector('#galy-ventas-7dias');
                if (!nodo || typeof ApexCharts === 'undefined') {
                    return;
                }
                new ApexCharts(nodo, {
                    chart: { type: 'bar', height: 260, toolbar: { show: false } },
                    series: [{ name: 'Ventas', data: datos.map(function (d) { return d.monto; }) }],
                    colors: ['#0d6efd'],
                    plotOptions: { bar: { borderRadius: 6, columnWidth: '45%' } },
                    xaxis: { categories: datos.map(function (d) { return d.label; }) },
                    yaxis: { labels: { formatter: function (v) { return 'S/ ' + Math.round(v); } } },
                    tooltip: { y: { formatter: function (v) { return 'S/ ' + v.toFixed(2); } } },
                    grid: { borderColor: '#e9ecef' },
                }).render();
            })();
        </script>
    @endif
@endsection
