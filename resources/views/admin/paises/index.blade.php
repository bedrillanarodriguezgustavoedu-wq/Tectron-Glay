@extends('layouts.admin')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Países de envío</h1>
            <p class="text-muted mb-0">Administra los destinos disponibles sin borrar sus direcciones ni zonas.</p>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Agregar país
        </button>
    </div>

    <div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-2" role="status">
        <span>Por ahora, Perú es el único país configurado para envíos. Los países nuevos se crean desactivados y sin envíos habilitados hasta que completes sus zonas.</span>
        @can('Gestionar zonas de envío')
            <span class="d-flex gap-2">
                <a href="{{ route('admin.departamentos.index') }}" class="btn btn-sm btn-outline-primary">Departamentos</a>
                <a href="{{ route('admin.provincias.index') }}" class="btn btn-sm btn-outline-primary">Provincias</a>
            </span>
        @endcan
    </div>

    @if (session('mensaje'))
        <div class="alert alert-{{ session('icono') === 'success' ? 'success' : 'info' }}" role="status">
            {{ session('mensaje') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-warning" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <form action="{{ route('admin.paises.index') }}" method="GET" class="row g-2">
                <div class="col-12 col-md-6 col-lg-4">
                    <label for="buscar-pais" class="visually-hidden">Buscar país</label>
                    <div class="input-group">
                        <input id="buscar-pais" type="search" name="buscar" class="form-control"
                            placeholder="Buscar país..." value="{{ request('buscar') }}">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="bi bi-search me-1" aria-hidden="true"></i>Buscar
                        </button>
                        @if (request('buscar'))
                            <a href="{{ route('admin.paises.index') }}" class="btn btn-outline-secondary">Limpiar</a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="ps-3">País</th>
                        <th scope="col">Zonas</th>
                        <th scope="col">Direcciones guardadas</th>
                        <th scope="col">Envíos</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="text-end pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($paises as $pais)
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $pais->nombre }}</td>
                            <td>{{ $pais->departamentos_count }}</td>
                            <td>{{ $pais->direcciones_count }}</td>
                            <td>
                                <span class="badge {{ $pais->realiza_envios && $pais->activo ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $pais->realiza_envios && $pais->activo ? 'Habilitados' : 'Deshabilitados' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $pais->activo ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $pais->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="text-end pe-3">
                                <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                    <button type="button" class="btn btn-outline-primary btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#editModal"
                                        data-update-url="{{ route('admin.paises.update', $pais->id) }}"
                                        data-country-name="{{ $pais->nombre }}"
                                        data-shipping-enabled="{{ $pais->realiza_envios ? '1' : '0' }}"
                                        data-country-active="{{ $pais->activo ? '1' : '0' }}">
                                        <i class="bi bi-pencil me-1" aria-hidden="true"></i>Editar
                                    </button>
                                    <form action="{{ route('admin.paises.toggle', $pais->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-secondary btn-sm"
                                            aria-label="{{ $pais->activo ? 'Desactivar' : 'Activar' }} {{ $pais->nombre }}">
                                            {{ $pais->activo ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                    @if ((int) $pais->departamentos_count === 0 && (int) $pais->direcciones_count === 0)
                                        <form action="{{ route('admin.paises.destroy', $pais->id) }}" method="POST"
                                            onsubmit="return confirm('¿Eliminar {{ $pais->nombre }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Eliminar</button>
                                        </form>
                                    @else
                                        <button type="button" class="btn btn-outline-danger btn-sm" disabled
                                            title="Tiene zonas o direcciones asociadas; puedes desactivarlo en su lugar.">
                                            Con datos
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">No hay países que coincidan con la búsqueda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($paises->hasPages())
            <div class="card-footer bg-white">
                {{ $paises->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="editCountryForm" method="POST" class="modal-content">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="editModalLabel">Editar país</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit-country-name" class="form-label">Nombre</label>
                        <input type="text" name="nombre" id="edit-country-name" class="form-control" maxlength="255" required>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="realiza_envios" value="1" id="edit-country-shipping">
                        <label class="form-check-label" for="edit-country-shipping">Permitir envíos a este país</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="activo" value="1" id="edit-country-active">
                        <label class="form-check-label" for="edit-country-active">País activo para clientes</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('admin.paises.store') }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="createModalLabel">Agregar país</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nombre_new" class="form-label">Nombre</label>
                        <input type="text" name="nombre" id="nombre_new" class="form-control"
                            value="{{ old('nombre') }}" maxlength="255" placeholder="Ej.: Chile" required>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="realiza_envios" value="1" id="envios_new">
                        <label class="form-check-label" for="envios_new">Permitir envíos a este país</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="activo" value="1" id="activo_new">
                        <label class="form-check-label" for="activo_new">País activo para clientes</label>
                    </div>
                    <p class="form-text mt-3 mb-0">Antes de habilitar envíos, configura sus departamentos y provincias.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear país</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('editModal')?.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            const form = document.getElementById('editCountryForm');
            form.action = button.dataset.updateUrl;
            document.getElementById('edit-country-name').value = button.dataset.countryName;
            document.getElementById('edit-country-shipping').checked = button.dataset.shippingEnabled === '1';
            document.getElementById('edit-country-active').checked = button.dataset.countryActive === '1';
        });
    </script>
@endsection
