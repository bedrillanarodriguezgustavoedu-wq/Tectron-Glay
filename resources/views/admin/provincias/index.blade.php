@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Gestión de Provincias / Ciudades</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="bi bi-plus-circle"></i> Nueva Provincia
        </button>
    </div>
    <hr>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <form action="{{ url('/admin/provincias') }}" method="GET" class="row g-2 align-items-center">
                        <div class="col-md-4">
                            <input type="text" name="buscar" class="form-control" placeholder="Buscar provincia..."
                                value="{{ request('buscar') }}">
                        </div>
                        <div class="col-md-4">
                            <select name="departamento_id" class="form-select">
                                <option value="">-- Todos los departamentos --</option>
                                @foreach ($departamentos as $dpto)
                                    <option value="{{ $dpto->id }}" {{ request('departamento_id') == $dpto->id ? 'selected' : '' }}>
                                        {{ $dpto->nombre }} ({{ $dpto->pais->nombre }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Filtrar
                            </button>
                            @if (request('buscar') || request('departamento_id'))
                                <a href="{{ url('/admin/provincias') }}" class="btn btn-secondary">
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
                                    <th>Nombre de la Provincia</th>
                                    <th>Departamento / Región</th>
                                    <th>País</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center" style="width: 25%">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($provincias as $prov)
                                    <tr>
                                        <td class="text-center">{{ $prov->id }}</td>
                                        <td class="fw-bold">{{ $prov->nombre }}</td>
                                        <td>
                                            <span class="badge bg-light-info text-info">
                                                <i class="bi bi-map"></i> {{ $prov->departamento->nombre }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light-primary text-primary">
                                                <i class="bi bi-globe"></i> {{ $prov->departamento->pais->nombre }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if ($prov->activo)
                                                <span class="badge bg-success">Activo</span>
                                            @else
                                                <span class="badge bg-danger">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <!-- Status toggle -->
                                                <form action="{{ route('admin.provincias.toggle', $prov->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-secondary btn-sm" title="Cambiar Estado">
                                                        <i class="bi bi-arrow-left-right"></i>
                                                    </button>
                                                </form>

                                                <!-- Edit button -->
                                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                                    data-bs-target="#editModal{{ $prov->id }}" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </button>

                                                <!-- Delete button -->
                                                <form action="{{ route('admin.provincias.destroy', $prov->id) }}" method="POST"
                                                    id="deleteForm{{ $prov->id }}" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                                        onclick="confirmDelete({{ $prov->id }})" title="Eliminar">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>

                                            <!-- Edit Modal for each province -->
                                            <div class="modal fade text-start" id="editModal{{ $prov->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Editar Provincia</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('admin.provincias.update', $prov->id) }}" method="POST">
                                                            @csrf
                                                            @method('PUT')
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold">Departamento</label>
                                                                    <select name="departamento_id" class="form-select" required>
                                                                        @foreach ($departamentos as $dpto)
                                                                            <option value="{{ $dpto->id }}" {{ $prov->departamento_id == $dpto->id ? 'selected' : '' }}>
                                                                                {{ $dpto->nombre }} ({{ $dpto->pais->nombre }})
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="nombre{{ $prov->id }}" class="form-label fw-bold">Nombre de la Provincia</label>
                                                                    <input type="text" name="nombre" id="nombre{{ $prov->id }}" class="form-control"
                                                                        value="{{ $prov->nombre }}" required>
                                                                </div>
                                                                <div class="form-check form-switch">
                                                                    <input class="form-check-input" type="checkbox" name="activo" value="1"
                                                                        id="activo{{ $prov->id }}" {{ $prov->activo ? 'checked' : '' }}>
                                                                    <label class="form-check-label fw-semibold" for="activo{{ $prov->id }}">Provincia activa</label>
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
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            No se encontraron provincias registradas.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($provincias->hasPages())
                    <div class="card-footer bg-white border-0 py-3">
                        {{ $provincias->links('pagination::bootstrap-5') }}
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
                    <h5 class="modal-title">Registrar Nueva Provincia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.provincias.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Departamento / Región</label>
                            <select name="departamento_id" class="form-select" required>
                                <option value="">-- Seleccionar departamento --</option>
                                @foreach ($departamentos as $dpto)
                                    <option value="{{ $dpto->id }}" {{ old('departamento_id') == $dpto->id ? 'selected' : '' }}>
                                        {{ $dpto->nombre }} ({{ $dpto->pais->nombre }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="nombre_new" class="form-label fw-bold">Nombre de la Provincia</label>
                            <input type="text" name="nombre" id="nombre_new" class="form-control" placeholder="ej: Chachapoyas, Santa, Trujillo" required>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="activo" value="1" id="activo_new" checked>
                            <label class="form-check-label fw-semibold" for="activo_new">Provincia activa</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Registrar Provincia</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(id) {
            Swal.fire({
                title: '¿Desea eliminar esta provincia?',
                text: "Esta acción eliminará la provincia de forma permanente.",
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
