@extends('layouts.admin')

@section('title', 'Resumen de la app · ' . ($ajuste->nombre ?? 'TECTRON'))

@section('content')
    <div class="ga-page-head">
        <div class="ga-page-head-main">
            <div class="ga-eyebrow">App GALY</div>
            <h1 class="ga-page-title">Resumen de la aplicación</h1>
            <p class="ga-page-sub">
                Ventas, clientes, stock y utilidad compartidos con la app Android. Los cambios que hagas aquí se reflejan en la app y en la tienda web.
            </p>
        </div>
        <div class="ga-head-actions">
            <a href="{{ route('admin.app-galy.clientes') }}" class="btn-ga-ghost">
                <i class="bi bi-people" aria-hidden="true"></i> Clientes app
            </a>
            <a href="{{ route('admin.app-galy.pedidos') }}" class="btn-ga-ghost">
                <i class="bi bi-receipt" aria-hidden="true"></i> Pedidos app
            </a>
            <a href="{{ route('admin.app-galy.comentarios') }}" class="btn-ga-ghost">
                <i class="bi bi-chat-left-text" aria-hidden="true"></i> Comentarios
            </a>
            <span class="ga-count-pill">
                <i class="bi bi-phone" aria-hidden="true"></i>
                <strong>{{ number_format($clientesTotal) }}</strong> clientes
            </span>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="ga-kpi">
                <div class="ga-kpi-top">
                    <div>
                        <p class="ga-kpi-label">Ventas totales</p>
                        <p class="ga-kpi-value">S/ {{ number_format($ventas, 2) }}</p>
                    </div>
                    <span class="ga-kpi-icon is-success"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i></span>
                </div>
                <span class="ga-kpi-foot"><i class="bi bi-cash-coin" aria-hidden="true"></i> App y web</span>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="ga-kpi">
                <div class="ga-kpi-top">
                    <div>
                        <p class="ga-kpi-label">Pedidos</p>
                        <p class="ga-kpi-value">{{ number_format($pedidosTotal) }}</p>
                    </div>
                    <span class="ga-kpi-icon"><i class="bi bi-receipt" aria-hidden="true"></i></span>
                </div>
                <span class="ga-kpi-foot"><i class="bi bi-calendar3" aria-hidden="true"></i> {{ number_format($pedidosMes) }} este mes</span>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="ga-kpi">
                <div class="ga-kpi-top">
                    <div>
                        <p class="ga-kpi-label">Clientes de la app</p>
                        <p class="ga-kpi-value">{{ number_format($clientesTotal) }}</p>
                    </div>
                    <span class="ga-kpi-icon is-info"><i class="bi bi-people-fill" aria-hidden="true"></i></span>
                </div>
                <span class="ga-kpi-foot"><i class="bi bi-phone" aria-hidden="true"></i> Cuentas Android</span>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="ga-kpi">
                <div class="ga-kpi-top">
                    <div>
                        <p class="ga-kpi-label">Productos</p>
                        <p class="ga-kpi-value">{{ number_format($productosTotal) }}</p>
                    </div>
                    <span class="ga-kpi-icon is-warning"><i class="bi bi-box-seam-fill" aria-hidden="true"></i></span>
                </div>
                <span class="ga-kpi-foot {{ $stockCritico > 0 ? 'text-danger' : '' }}">
                    <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                    {{ number_format($stockCritico) }} con stock bajo
                </span>
            </div>
        </div>
    </div>

    <div class="ga-surface mb-3" id="funciones-app">
        <div class="ga-card-head">
            <div>
                <h2>Funciones del administrador de la app</h2>
                <p>Lo mismo que ves en el panel Android, pero desde la tienda web. Los datos son los mismos.</p>
            </div>
            <span class="badge bg-primary">{{ count($funciones) }} funciones</span>
        </div>
        <div class="card-body">
            <div class="row g-2">
                @foreach ($funciones as $funcion)
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <a href="{{ route($funcion['ruta']) }}" class="ga-quick">
                            <span class="ga-quick-icon"><i class="bi {{ $funcion['icono'] }}" aria-hidden="true"></i></span>
                            <span class="flex-grow-1">
                                <strong>{{ $funcion['titulo'] }}</strong>
                                <small>{{ $funcion['descripcion'] }}</small>
                            </span>
                            @if ($funcion['contador'] !== null)
                                <span class="badge bg-secondary flex-shrink-0">
                                    {{ number_format($funcion['contador']) }}
                                </span>
                            @endif
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-8">
            <div class="ga-surface h-100">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h2 class="h5 mb-1">Meta de ventas del mes</h2>
                            <p class="ga-muted mb-0 small">Ventas registradas este mes frente a la meta configurada.</p>
                        </div>
                        <span class="ga-count-pill">
                            <i class="bi bi-bullseye" aria-hidden="true"></i>
                            <strong>{{ $porcentajeMeta }}%</strong>
                        </span>
                    </div>
                    <div class="progress mb-2" role="progressbar" aria-label="Avance de la meta mensual"
                        aria-valuenow="{{ $porcentajeMeta }}" aria-valuemin="0" aria-valuemax="100" style="height: 12px;">
                        <div class="progress-bar {{ $porcentajeMeta >= 100 ? 'bg-success' : '' }}"
                            style="width: {{ min(100, $porcentajeMeta) }}%;"></div>
                    </div>
                    <div class="d-flex justify-content-between small ga-muted">
                        <span>S/ {{ number_format($ventasMes, 2) }} vendidos</span>
                        <span>Meta: S/ {{ number_format($metaMensual, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="ga-surface h-100">
                <div class="card-body">
                    <p class="ga-kpi-label mb-1">Utilidad potencial del inventario</p>
                    <h2 class="h4 mb-1">S/ {{ number_format($utilidadPotencial, 2) }}</h2>
                    <p class="ga-muted small mb-0">
                        Costo del inventario: S/ {{ number_format($inventarioCosto, 2) }}
                    </p>
                    <hr>
                    <div class="d-flex justify-content-between small">
                        <span class="ga-muted">Comentarios de clientes</span>
                        <strong>{{ number_format($comentariosTotal) }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($serieVentas !== [])
        <div class="ga-chart-card mb-3">
            <div class="ga-chart-head">
                <div>
                    <h2>Ventas de los últimos 7 días</h2>
                    <p>Pedidos pagados de la app y de la web, sin contar cancelados.</p>
                </div>
                <a href="{{ route('admin.app-galy.pedidos') }}" class="btn-ga-ghost">
                    <i class="bi bi-receipt" aria-hidden="true"></i> Ver pedidos de la app
                </a>
            </div>
            <div class="ga-chart-body">
                <div id="galy-ventas-7dias" role="img" aria-label="Gráfico de ventas de los últimos siete días"></div>
            </div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="ga-surface ga-table-card h-100">
                <div class="ga-card-head">
                    <div>
                        <h2>Productos más vendidos</h2>
                        <p>Ranking por unidades vendidas</p>
                    </div>
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
                                    <td class="text-end"><span class="badge bg-primary">{{ number_format((int) $producto->total_vendido) }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2">
                                        <div class="ga-empty py-4">
                                            <span class="ga-empty-icon"><i class="bi bi-graph-up" aria-hidden="true"></i></span>
                                            <strong>Todavía no hay ventas registradas</strong>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="ga-surface ga-table-card h-100">
                <div class="ga-card-head">
                    <div>
                        <h2>Stock crítico</h2>
                        <p>Productos que necesitan reposición</p>
                    </div>
                    <a href="{{ route('admin.productos.index') }}" class="btn-ga-ghost">
                        <i class="bi bi-box-seam" aria-hidden="true"></i> Administrar
                    </a>
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
                                        <span class="badge {{ (int) $producto->stock <= 0 ? 'bg-danger' : 'bg-warning' }}">
                                            {{ (int) $producto->stock }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2">
                                        <div class="ga-empty py-4">
                                            <span class="ga-empty-icon"><i class="bi bi-check2-circle" aria-hidden="true"></i></span>
                                            <strong>No hay productos con stock bajo</strong>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="ga-surface ga-table-card h-100">
                <div class="ga-card-head">
                    <div>
                        <h2>Últimos pedidos de la app</h2>
                        <p>Actividad reciente desde Android</p>
                    </div>
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
                                    <td><strong>#{{ $pedido->id }}</strong></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ $pedido->estado ?: $pedido->estado_orden }}
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold">S/ {{ number_format((float) $pedido->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3">
                                        <div class="ga-empty py-4">
                                            <span class="ga-empty-icon"><i class="bi bi-receipt" aria-hidden="true"></i></span>
                                            <strong>No hay pedidos todavía</strong>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="ga-surface ga-table-card h-100">
                <div class="ga-card-head">
                    <div>
                        <h2>Ventas por distrito</h2>
                        <p>Dónde compran tus clientes</p>
                    </div>
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
                                    <td class="text-end fw-bold">S/ {{ number_format($distrito['monto'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2">
                                        <div class="ga-empty py-4">
                                            <span class="ga-empty-icon"><i class="bi bi-geo-alt" aria-hidden="true"></i></span>
                                            <strong>Sin datos por distrito</strong>
                                        </div>
                                    </td>
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
                var oscuro = document.documentElement.getAttribute('data-bs-theme') === 'dark';
                new ApexCharts(nodo, {
                    chart: { type: 'bar', height: 260, toolbar: { show: false }, fontFamily: 'Inter, system-ui, sans-serif' },
                    series: [{ name: 'Ventas', data: datos.map(function (d) { return d.monto; }) }],
                    colors: ['#3b5bdb'],
                    plotOptions: { bar: { borderRadius: 6, columnWidth: '45%' } },
                    xaxis: {
                        categories: datos.map(function (d) { return d.label; }),
                        labels: { style: { colors: oscuro ? '#94a3b8' : '#64748b' } }
                    },
                    yaxis: {
                        labels: {
                            style: { colors: oscuro ? '#94a3b8' : '#64748b' },
                            formatter: function (v) { return 'S/ ' + Math.round(v); }
                        }
                    },
                    tooltip: { theme: oscuro ? 'dark' : 'light', y: { formatter: function (v) { return 'S/ ' + v.toFixed(2); } } },
                    grid: { borderColor: oscuro ? '#1e2c47' : '#e9ecef' },
                }).render();
            })();
        </script>
    @endif
@endsection
