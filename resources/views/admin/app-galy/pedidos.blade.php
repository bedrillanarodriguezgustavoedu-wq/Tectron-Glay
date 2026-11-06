@extends('layouts.admin')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <div class="text-uppercase text-primary fw-semibold small mb-1">App GALY</div>
            <h1 class="mb-1">Pedidos de la aplicación</h1>
            <p class="text-muted mb-0">
                Los pedidos que llegan desde la app Android y la tienda web viven en la misma tabla: aquí ves los de
                la app con su cliente, estado y total.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.app-galy.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Resumen
            </a>
            @can('Listado de Pedidos')
                <a href="{{ route('admin.pedidos.index') }}" class="btn btn-outline-primary">
                    <i class="bi bi-card-checklist" aria-hidden="true"></i> Todos los pedidos
                </a>
            @endcan
            <a href="{{ route('admin.app-galy.pedidos.exportar', request()->only(['buscar', 'estado'])) }}"
                class="btn btn-outline-success">
                <i class="bi bi-filetype-csv" aria-hidden="true"></i> Exportar CSV
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.app-galy.pedidos') }}" method="GET" class="row g-2 align-items-end mb-3">
                <div class="col-12 col-md-5">
                    <label for="buscar" class="form-label small text-muted mb-1">Buscar</label>
                    <input type="text" class="form-control" id="buscar" name="buscar" value="{{ $buscar }}"
                        placeholder="N.º de pedido, cliente o correo">
                </div>
                <div class="col-12 col-md-3">
                    <label for="estado" class="form-label small text-muted mb-1">Estado</label>
                    <select name="estado" id="estado" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($estados as $opcion)
                            <option value="{{ $opcion }}" @selected($estado === $opcion)>{{ $opcion }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search" aria-hidden="true"></i> Filtrar
                    </button>
                    @if ($buscar !== '' || $estado !== '')
                        <a href="{{ route('admin.app-galy.pedidos') }}" class="btn btn-outline-secondary">Limpiar</a>
                    @endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Cliente de la app</th>
                            <th>Fecha</th>
                            <th>Zona</th>
                            <th>Pago</th>
                            <th>Estado</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pedidos as $pedido)
                            <tr>
                                <td><strong>#{{ $pedido->id }}</strong></td>
                                <td>
                                    @if (! empty($pedido->usuario_nombre))
                                        <a href="{{ route('admin.app-galy.cliente', $pedido->usuario_id) }}"
                                            class="text-decoration-none">{{ $pedido->usuario_nombre }}</a>
                                        <div class="small text-muted">{{ $pedido->usuario_email }}</div>
                                    @elseif (! empty($pedido->cliente_nombre))
                                        {{ $pedido->cliente_nombre }}
                                        <div class="small text-muted">{{ $pedido->cliente_email }}</div>
                                    @else
                                        <span class="text-muted">Cliente sin cuenta</span>
                                    @endif
                                </td>
                                <td class="small">
                                    {{ $pedido->created_at ? \Illuminate\Support\Carbon::parse($pedido->created_at)->format('d/m/Y H:i') : '—' }}
                                </td>
                                <td class="small">{{ $pedido->distrito ?: '—' }}</td>
                                <td class="small text-uppercase">{{ $pedido->metodo_pago ?: '—' }}</td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary">
                                        {{ $pedido->estado ?: $pedido->estado_orden ?: 'Sin estado' }}
                                    </span>
                                    @if (! empty($pedido->estado_orden) && $pedido->estado !== $pedido->estado_orden)
                                        <div class="small text-muted mt-1">{{ $pedido->estado_orden }}</div>
                                    @endif
                                </td>
                                <td class="text-end">S/ {{ number_format((float) $pedido->total, 2) }}</td>
                                <td class="text-end">
                                    @can('Listado de Pedidos')
                                        <a href="{{ route('admin.pedidos.create', $pedido->id) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Gestionar
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    No hay pedidos que coincidan con esta búsqueda.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($pedidos->hasPages())
                <div class="mt-3">{{ $pedidos->links() }}</div>
            @endif
        </div>
    </div>
@endsection
