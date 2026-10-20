@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Mensajes de Contacto</h1>
    </div>
    <hr>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <form action="{{ url('/admin/mensajes') }}" method="GET" class="row g-2 align-items-center">
                        <div class="col-md-5">
                            <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre, email, asunto, mensaje..."
                                value="{{ request('buscar') }}">
                        </div>
                        <div class="col-md-3">
                            <select name="estado" class="form-select">
                                <option value="">-- Todos los estados --</option>
                                <option value="Pendiente" {{ request('estado') === 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
                                <option value="Leído" {{ request('estado') === 'Leído' ? 'selected' : '' }}>Leído</option>
                                <option value="Respondido" {{ request('estado') === 'Respondido' ? 'selected' : '' }}>Respondido</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Filtrar
                            </button>
                            @if (request('buscar') || request('estado'))
                                <a href="{{ url('/admin/mensajes') }}" class="btn btn-secondary">
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
                                    <th class="text-center" style="width: 8%">ID</th>
                                    <th>Remitente</th>
                                    <th>Asunto</th>
                                    <th>Fecha y Hora</th>
                                    <th class="text-center" style="width: 15%">Estado</th>
                                    <th class="text-center" style="width: 15%">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($mensajes as $msg)
                                    <tr class="{{ $msg->estado === 'Pendiente' ? 'table-warning' : '' }}">
                                        <td class="text-center">{{ $msg->id }}</td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $msg->nombre }}</div>
                                            <small class="text-muted"><i class="bi bi-envelope"></i> {{ $msg->email }}</small>
                                        </td>
                                        <td>
                                            <span class="text-truncate d-inline-block fw-semibold" style="max-width: 300px;">
                                                {{ $msg->asunto }}
                                            </span>
                                        </td>
                                        <td>
                                            <div>{{ $msg->created_at->format('d/m/Y') }}</div>
                                            <small class="text-muted"><i class="bi bi-clock"></i> {{ $msg->created_at->format('H:i A') }}</small>
                                        </td>
                                        <td class="text-center">
                                            @if ($msg->estado === 'Pendiente')
                                                <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> Pendiente</span>
                                            @elseif ($msg->estado === 'Leído')
                                                <span class="badge bg-info text-white"><i class="bi bi-envelope-open"></i> Leído</span>
                                            @else
                                                <span class="badge bg-success text-white"><i class="bi bi-check-circle-fill"></i> Respondido</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                @can('Ver Mensaje')
                                                    <a href="{{ route('admin.mensajes.show', $msg->id) }}" class="btn btn-outline-primary btn-sm" title="Ver Detalle">
                                                        <i class="bi bi-eye"></i> Ver
                                                    </a>
                                                @endcan

                                                @can('Eliminar Mensaje')
                                                    <form action="{{ route('admin.mensajes.destroy', $msg->id) }}" method="POST"
                                                        id="deleteForm{{ $msg->id }}" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="btn btn-outline-danger btn-sm"
                                                            onclick="confirmDelete({{ $msg->id }})" title="Eliminar">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="bi bi-chat-square-text display-4 text-muted"></i>
                                            <p class="mt-2 mb-0">No se encontraron mensajes en la bandeja de entrada.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($mensajes->hasPages())
                    <div class="card-footer bg-white border-0 py-3">
                        {{ $mensajes->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(id) {
            Swal.fire({
                title: '¿Desea eliminar este mensaje?',
                text: "Esta acción removerá permanentemente el mensaje de contacto.",
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
