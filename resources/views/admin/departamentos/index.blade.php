@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Gestión de Departamentos / Regiones</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="bi bi-plus-circle"></i> Nuevo Departamento
        </button>
    </div>
    <hr>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <form action="{{ url('/admin/departamentos') }}" method="GET" class="row g-2 align-items-center">
                        <div class="col-md-4">
                            <input type="text" name="buscar" class="form-control" placeholder="Buscar departamento..."
                                value="{{ request('buscar') }}">
                        </div>
                        <div class="col-md-4">
                            <select name="pais_id" class="form-select">
                                <option value="">-- Todos los países --</option>
                                @foreach ($paises as $pais)
                                    <option value="{{ $pais->id }}" {{ request('pais_id') == $pais->id ? 'selected' : '' }}>
                                        {{ $pais->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Filtrar
                            </button>
                            @if (request('buscar') || request('pais_id'))
                                <a href="{{ url('/admin/departamentos') }}" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Limpiar
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 10%">ID</th>
                                    <th>Nombre del Departamento</th>
                                    <th>País</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center" style="width: 25%">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($departamentos as $dpto)
                                    <tr>
                                        <td class="text-center">{{ $dpto->id }}</td>
                                        <td class="fw-bold">{{ $dpto->nombre }}</td>
                                        <td>
                                            <span class="badge bg-light-primary text-primary">
                                                <i class="bi bi-globe"></i> {{ $dpto->pais->nombre }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if ($dpto->activo)
                                                <span class="badge bg-success">Activo</span>
                                            @else
                                                <span class="badge bg-danger">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <!-- Status toggle -->
                                                <form action="{{ route('admin.departamentos.toggle', $dpto->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-secondary btn-sm" title="Cambiar Estado">
                                                        <i class="bi bi-arrow-left-right"></i>
                                                    </button>
                                                </form>

                                                <!-- Edit button -->
                                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                                    data-bs-target="#editModal{{ $dpto->id }}" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </button>

                                                <!-- Delete button -->
                                                <form action="{{ route('admin.departamentos.destroy', $dpto->id) }}" method="POST"
                                                    id="deleteForm{{ $dpto->id }}" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                                        onclick="confirmDelete({{ $dpto->id }})" title="Eliminar">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>

                                            <!-- Edit Modal for each department -->
                                            <div class="modal fade text-start" id="editModal{{ $dpto->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Editar Departamento</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('admin.departamentos.update', $dpto->id) }}" method="POST">
                                                            @csrf
                                                            @method('PUT')
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold">País</label>
                                                                    <select name="pais_id" class="form-select" required>
                                                                        @foreach ($paises as $pais)
                                                                            <option value="{{ $pais->id }}" {{ $dpto->pais_id == $pais->id ? 'selected' : '' }}>
                                                                                {{ $pais->nombre }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="nombre{{ $dpto->id }}" class="form-label fw-bold">Nombre del Departamento</label>
                                                                    <input type="text" name="nombre" id="nombre{{ $dpto->id }}" class="form-control"
                                                                        value="{{ $dpto->nombre }}" required>
                                                                </div>
                                                                <div class="form-check form-switch">
                                                                    <input class="form-check-input" type="checkbox" name="activo" value="1"
                                                                        id="activo{{ $dpto->id }}" {{ $dpto->activo ? 'checked' : '' }}>
                                                                    <label class="form-check-label fw-semibold" for="activo{{ $dpto->id }}">Departamento activo</label>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            No se encontraron departamentos registrados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($departamentos->hasPages())
                    <div class="card-footer bg-white border-0 py-3">
                        {{ $departamentos->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Create Modal -->
    <div class="modal fade text-start" id="createModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Registrar Nuevo Departamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.departamentos.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">País</label>
                            <select name="pais_id" class="form-select" required>
                                <option value="">-- Seleccionar país --</option>
                                @foreach ($paises as $pais)
                                    <option value="{{ $pais->id }}" {{ old('pais_id') == $pais->id ? 'selected' : '' }}>
                                        {{ $pais->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="nombre_new" class="form-label fw-bold">Nombre del Departamento / Estado</label>
                            <input type="text" name="nombre" id="nombre_new" class="form-control" placeholder="ej: Lima, Arequipa, Cusco" required>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="activo" value="1" id="activo_new" checked>
                            <label class="form-check-label fw-semibold" for="activo_new">Departamento activo</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Registrar Departamento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(id) {
            Swal.fire({
                title: '¿Desea eliminar este departamento?',
                text: "Esta acción también eliminará todas sus provincias asociadas de manera permanente.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('deleteForm' + id).submit();
                }
            });
        }
    </script>
@endsection
