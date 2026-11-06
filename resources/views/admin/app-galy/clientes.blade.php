@extends('layouts.admin')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <div class="text-uppercase text-primary fw-semibold small mb-1">App GALY</div>
            <h1 class="mb-1">Clientes de la aplicación</h1>
            <p class="text-muted mb-0">Cuentas registradas desde la app Android. Edita sus datos, puntos y permisos.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <a href="{{ route('admin.app-galy.pedidos') }}" class="btn btn-outline-secondary">
                <i class="bi bi-receipt" aria-hidden="true"></i> Pedidos de la app
            </a>
            <a href="{{ route('admin.app-galy.administradores') }}" class="btn btn-outline-secondary">
                <i class="bi bi-shield-lock" aria-hidden="true"></i> Administradores
            </a>
            <span class="badge bg-primary-subtle text-primary fs-6">{{ number_format($clientes->total()) }} clientes</span>
        </div>
    </div>

    @if (session('mensaje'))
        <div class="alert alert-success alert-dismissible fade show" role="status">
            {{ session('mensaje') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.app-galy.clientes') }}" method="GET" class="row g-2 align-items-center mb-3">
                <div class="col-12 col-md-6 col-lg-5">
                    <div class="input-group">
                        <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre, correo o teléfono"
                            value="{{ $buscar }}">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search" aria-hidden="true"></i> Buscar
                        </button>
                    </div>
                </div>
                <div class="col-12 col-md-3 col-lg-2">
                    <select name="filtro" class="form-select" aria-label="Filtrar clientes">
                        <option value="">Todos</option>
                        <option value="admin" @selected($filtro === 'admin')>Administradores</option>
                        <option value="activos" @selected($filtro === 'activos')>Activos</option>
                        <option value="inactivos" @selected($filtro === 'inactivos')>Inactivos</option>
                    </select>
                </div>
                <div class="col-12 col-md-3 col-lg-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel" aria-hidden="true"></i> Filtrar
                    </button>
                    @if ($buscar !== '' || $filtro !== '')
                        <a href="{{ route('admin.app-galy.clientes') }}" class="btn btn-outline-secondary">Limpiar</a>
                    @endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Contacto</th>
                            <th class="text-center">Pedidos</th>
                            <th class="text-end">Total gastado</th>
                            <th class="text-center">Puntos</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clientes as $cliente)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if (! empty($cliente->imagen_url))
                                            <img src="{{ rtrim(config('galy.api_url'), '/') . '/img/' . rawurlencode(basename($cliente->imagen_url)) }}"
                                                alt="" width="36" height="36" class="rounded-circle object-fit-cover"
                                                onerror="this.style.display='none'">
                                        @else
                                            <span class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center"
                                                style="width:36px;height:36px;">
                                                <i class="bi bi-person" aria-hidden="true"></i>
                                            </span>
                                        @endif
                                        <div>
                                            <strong>{{ $cliente->nombre }}</strong>
                                            @if ((int) $cliente->es_admin === 1)
                                                <span class="badge bg-dark-subtle text-dark ms-1">Admin</span>
                                            @endif
                                            <div class="small text-muted">#{{ $cliente->id }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>{{ $cliente->email }}</div>
                                    @if (! empty($cliente->telefono))
                                        <div class="small text-muted">{{ $cliente->telefono }}</div>
                                    @endif
                                </td>
                                <td class="text-center">{{ number_format((int) ($cliente->pedidos_count ?? 0)) }}</td>
                                <td class="text-end">S/ {{ number_format((float) ($cliente->total_gastado ?? 0), 2) }}</td>
                                <td class="text-center">{{ number_format((int) ($cliente->puntos ?? 0)) }}</td>
                                <td class="text-center">
                                    @if ((int) ($cliente->estado ?? 1) === 1)
                                        <span class="badge bg-success-subtle text-success">Activo</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger">Inactivo</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.app-galy.cliente', $cliente->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye" aria-hidden="true"></i> Ver
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    No se encontraron clientes de la app.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($clientes->hasPages())
                <div class="mt-3">
                    {{ $clientes->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
