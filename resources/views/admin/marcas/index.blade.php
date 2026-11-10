@extends('layouts.admin')

@section('title', 'Marcas · ' . ($ajuste->nombre ?? 'TECTRON'))

@section('content')
    @php
        $marcasSinLogo = max(0, $totalMarcas - $marcasConLogo);
    @endphp

    <div class="ga-page-head">
        <div class="ga-page-head-main">
            <div class="ga-eyebrow">Catálogo compartido</div>
            <h1 class="ga-page-title">Marcas</h1>
            <p class="ga-page-sub">
                Administra los fabricantes del catálogo. Cada logo se muestra en la tienda, en los filtros del catálogo y en la app GALY.
            </p>
        </div>
        <div class="ga-head-actions">
            <a href="{{ route('web.marcas') }}" target="_blank" rel="noopener" class="btn-ga-ghost">
                <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Ver página pública
            </a>
            @can('Crear Marca')
                <a href="{{ route('admin.marcas.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i> Crear marca
                </a>
            @endcan
            <span class="ga-count-pill">
                <i class="bi bi-award" aria-hidden="true"></i>
                <strong>{{ number_format($totalMarcas) }}</strong> marcas
            </span>
        </div>
    </div>

    @if (session('mensaje'))
        <div class="alert alert-{{ session('icono') === 'success' ? 'success' : 'info' }} alert-dismissible fade show" role="status">
            {{ session('mensaje') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="ga-kpi">
                <div class="ga-kpi-top">
                    <div>
                        <p class="ga-kpi-label">Total de marcas</p>
                        <p class="ga-kpi-value">{{ number_format($totalMarcas) }}</p>
                    </div>
                    <span class="ga-kpi-icon"><i class="bi bi-award-fill" aria-hidden="true"></i></span>
                </div>
                <span class="ga-kpi-foot"><i class="bi bi-shop" aria-hidden="true"></i> Web y app GALY</span>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="ga-kpi">
                <div class="ga-kpi-top">
                    <div>
                        <p class="ga-kpi-label">Activas</p>
                        <p class="ga-kpi-value">{{ number_format($marcasActivas) }}</p>
                    </div>
                    <span class="ga-kpi-icon is-success"><i class="bi bi-check2-circle" aria-hidden="true"></i></span>
                </div>
                <span class="ga-kpi-foot"><i class="bi bi-eye" aria-hidden="true"></i> Visibles en la tienda</span>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="ga-kpi">
                <div class="ga-kpi-top">
                    <div>
                        <p class="ga-kpi-label">Con logo</p>
                        <p class="ga-kpi-value">{{ number_format($marcasConLogo) }}</p>
                    </div>
                    <span class="ga-kpi-icon is-info"><i class="bi bi-image-fill" aria-hidden="true"></i></span>
                </div>
                <span class="ga-kpi-foot"><i class="bi bi-check2" aria-hidden="true"></i> Imagen cargada</span>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="ga-kpi">
                <div class="ga-kpi-top">
                    <div>
                        <p class="ga-kpi-label">Sin logo</p>
                        <p class="ga-kpi-value">{{ number_format($marcasSinLogo) }}</p>
                    </div>
                    <span class="ga-kpi-icon {{ $marcasSinLogo > 0 ? 'is-warning' : 'is-success' }}">
                        <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                    </span>
                </div>
                <span class="ga-kpi-foot">
                    <i class="bi bi-{{ $marcasSinLogo > 0 ? 'pencil' : 'check2-all' }}" aria-hidden="true"></i>
                    {{ $marcasSinLogo > 0 ? 'Muestran sus iniciales' : 'Todas tienen logo' }}
                </span>
            </div>
        </div>
    </div>

    <div class="ga-surface ga-table-card">
        <div class="ga-card-head">
            <div>
                <h2>Marcas registradas</h2>
                <p>Ordenadas alfabéticamente · el logo se usa en la web y en la app</p>
            </div>
            @if ($marcas->hasPages())
                <span class="badge bg-secondary">
                    Página {{ $marcas->currentPage() }} de {{ $marcas->lastPage() }}
                </span>
            @endif
        </div>

        <div class="card-body">
            <form action="{{ route('admin.marcas.index') }}" method="GET" class="ga-toolbar mb-3" role="search">
                <div class="ga-search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <label class="visually-hidden" for="buscar-marcas">Buscar marcas</label>
                    <input type="search" id="buscar-marcas" name="buscar" class="form-control"
                        placeholder="Buscar por nombre de marca" value="{{ request('buscar') }}">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search" aria-hidden="true"></i> Buscar
                </button>
                @if (request('buscar'))
                    <a href="{{ route('admin.marcas.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg" aria-hidden="true"></i> Limpiar
                    </a>
                @endif
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 52px">Nro</th>
                            <th>Marca</th>
                            <th class="text-center">Productos</th>
                            <th class="text-center">Estado</th>
                            <th>Registrada</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $nro = ($marcas->currentPage() - 1) * $marcas->perPage() + 1;
                        @endphp
                        @forelse ($marcas as $marca)
                            <tr>
                                <td class="ga-muted">{{ $nro++ }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="ga-brand-thumb">
                                            <x-marca-logo :marca="$marca" width="96px" height="48px" />
                                        </span>
                                        <span class="min-w-0">
                                            <strong class="d-block">{{ $marca->nombre }}</strong>
                                            <small class="ga-muted d-block text-truncate" style="max-width: 220px;">
                                                {{ $marca->slug }}
                                                @if (! $marca->image_url)
                                                    · <span class="text-warning-emphasis">Sin logo</span>
                                                @endif
                                            </small>
                                        </span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">{{ number_format($marca->productos_count) }}</span>
                                </td>
                                <td class="text-center">
                                    <form action="{{ route('admin.marcas.toggle', $marca->id) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                            class="btn btn-sm {{ $marca->activo ? 'btn-outline-success' : 'btn-outline-danger' }}"
                                            title="{{ $marca->activo ? 'Desactivar marca' : 'Activar marca' }}">
                                            <i class="bi bi-{{ $marca->activo ? 'toggle-on' : 'toggle-off' }}" aria-hidden="true"></i>
                                            {{ $marca->activo ? 'Activa' : 'Inactiva' }}
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <small class="ga-muted d-block">
                                        <i class="bi bi-calendar3" aria-hidden="true"></i>
                                        {{ $marca->created_at?->format('d/m/Y') }}
                                    </small>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm" role="group" aria-label="Acciones de la marca {{ $marca->nombre }}">
                                        <a href="{{ route('admin.marcas.show', $marca->id) }}" class="btn btn-outline-secondary" title="Ver marca">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                            <span class="visually-hidden">Ver</span>
                                        </a>
                                        @can('Editar Marca')
                                            <a href="{{ route('admin.marcas.edit', $marca->id) }}" class="btn btn-outline-primary" title="Editar marca">
                                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                                <span class="visually-hidden">Editar</span>
                                            </a>
                                        @endcan
                                        @can('Eliminar Marca')
                                            <button type="button" class="btn btn-outline-danger" title="Desactivar marca"
                                                onclick="confirmarEliminar{{ $marca->id }}(event)">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                                <span class="visually-hidden">Desactivar</span>
                                            </button>
                                        @endcan
                                    </div>

                                    @can('Eliminar Marca')
                                        <form action="{{ route('admin.marcas.destroy', $marca->id) }}" method="POST"
                                            id="form-eliminar-{{ $marca->id }}" class="d-none">
                                            @csrf
                                            @method('DELETE')
                                        </form>

                                        <script>
                                            function confirmarEliminar{{ $marca->id }}(event) {
                                                event.preventDefault();
                                                Swal.fire({
                                                    title: '¿Desactivar la marca "{{ $marca->nombre }}"?',
                                                    text: 'Dejará de mostrarse en la tienda, pero sus productos y su logo se conservan.',
                                                    icon: 'warning',
                                                    showCancelButton: true,
                                                    confirmButtonColor: '#a5161d',
                                                    cancelButtonColor: '#334155',
                                                    confirmButtonText: 'Sí, desactivar',
                                                    cancelButtonText: 'Cancelar'
                                                }).then((result) => {
                                                    if (result.isConfirmed) {
                                                        document.getElementById('form-eliminar-{{ $marca->id }}').submit();
                                                    }
                                                });
                                            }
                                        </script>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="ga-empty">
                                        <span class="ga-empty-icon"><i class="bi bi-award" aria-hidden="true"></i></span>
                                        <strong>{{ request('buscar') ? 'No encontramos marcas con ese nombre' : 'Todavía no hay marcas registradas' }}</strong>
                                        <p>{{ request('buscar') ? 'Prueba con otro nombre o limpia la búsqueda.' : 'Crea la primera marca para organizar el catálogo de la tienda.' }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($marcas->hasPages())
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4 pt-3 border-top" style="border-color: var(--ga-line) !important;">
                    <div class="ga-muted small">
                        <i class="bi bi-list-ol" aria-hidden="true"></i>
                        Mostrando {{ $marcas->firstItem() }} a {{ $marcas->lastItem() }} de
                        {{ $marcas->total() }} marcas
                    </div>
                    <div>
                        {{ $marcas->appends(request()->query())->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
