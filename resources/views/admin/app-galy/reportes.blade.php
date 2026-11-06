@extends('layouts.admin')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <div class="text-uppercase text-primary fw-semibold small mb-1">App GALY</div>
            <h1 class="mb-1">Reportes y ganancias</h1>
            <p class="text-muted mb-0">
                Los mismos reportes que genera el admin de la app, pero desde la tienda web. Se calculan con la tabla
                <code>pedidos</code> compartida, así que coinciden con lo que ves en el teléfono.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2 no-print">
            <a href="{{ route('admin.app-galy.pedidos.exportar') }}" class="btn btn-outline-primary">
                <i class="bi bi-filetype-csv" aria-hidden="true"></i> Exportar pedidos
            </a>
            <a href="{{ route('admin.app-galy.clientes.exportar') }}" class="btn btn-outline-primary">
                <i class="bi bi-filetype-csv" aria-hidden="true"></i> Exportar clientes
            </a>
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-printer" aria-hidden="true"></i> Guardar como PDF
            </button>
        </div>
    </div>

    <form action="{{ route('admin.app-galy.reportes') }}" method="GET" class="row g-2 align-items-end mb-4 no-print">
        <div class="col-6 col-md-3">
            <label for="mes" class="form-label small text-muted mb-1">Mes</label>
            <select name="mes" id="mes" class="form-select">
                @foreach (range(1, 12) as $opcion)
                    <option value="{{ $opcion }}" @selected($mes === $opcion)>{{ $opcion }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label for="anio" class="form-label small text-muted mb-1">Año</label>
            <select name="anio" id="anio" class="form-select">
                @foreach (range(now()->year, now()->year - 4) as $opcion)
                    <option value="{{ $opcion }}" @selected($anio === $opcion)>{{ $opcion }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-6 d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-search" aria-hidden="true"></i> Calcular
            </button>
            @if ($mes !== (int) now()->month || $anio !== (int) now()->year)
                <a href="{{ route('admin.app-galy.reportes') }}" class="btn btn-outline-secondary">Mes actual</a>
            @endif
        </div>
    </form>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Ventas del período</div>
                    <div class="h4 mb-0">S/ {{ number_format($ventasMes, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Pedidos</div>
                    <div class="h4 mb-0">{{ number_format($pedidosMes) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Ticket promedio</div>
                    <div class="h4 mb-0">S/ {{ number_format($ticketPromedio, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Utilidad estimada</div>
                    <div class="h4 mb-0 text-success">S/ {{ number_format($utilidadMes, 2) }}</div>
                    <div class="small text-muted">Ventas menos costo de los productos vendidos.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent">
            <h2 class="h6 mb-0">Ventas por día · {{ $mes }}/{{ $anio }}</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Día</th>
                            <th class="text-end">Pedidos</th>
                            <th class="text-end">Ventas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($porDia as $dia)
                            <tr>
                                <td>{{ \Illuminate\Support\Carbon::parse($dia['dia'])->format('d/m/Y') }}</td>
                                <td class="text-end">{{ number_format($dia['pedidos']) }}</td>
                                <td class="text-end">S/ {{ number_format($dia['monto'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-5">
                                    No hay pedidos registrados en este período.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent">
            <h2 class="h6 mb-0">Productos más vendidos (histórico)</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="text-end">Unidades</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topProductos as $producto)
                            <tr>
                                <td>{{ $producto->nombre }}</td>
                                <td class="text-end">{{ number_format((int) $producto->unidades) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted py-5">
                                    Todavía no hay productos vinculados a los pedidos de la app.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @style
        @media print {
            .no-print {
                display: none !important;
            }
        }
    @endstyle
@endsection
