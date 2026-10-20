@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Detalle de Mensaje de Contacto</h1>
        <a href="{{ route('admin.mensajes.index') }}" class="btn btn-secondary rounded-pill">
            <i class="bi bi-arrow-left"></i> Volver a la Bandeja
        </a>
    </div>
    <hr>

    <div class="row">
        <div class="col-lg-8 col-md-12">
            <div class="card shadow-sm rounded-4 border-0">
                <div class="card-header bg-light border-0 py-3 d-flex justify-content-between align-items-center">
                    <span class="fs-5 fw-bold text-dark">Bandeja de Entrada - Mensaje #{{ $mensaje->id }}</span>
                    @if ($mensaje->estado === 'Pendiente')
                        <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> Pendiente</span>
                    @elseif ($mensaje->estado === 'Leído')
                        <span class="badge bg-info text-white"><i class="bi bi-envelope-open"></i> Leído</span>
                    @else
                        <span class="badge bg-success text-white"><i class="bi bi-check-circle-fill"></i> Respondido</span>
                    @endif
                </div>
                <div class="card-body py-4">
                    <div class="row mb-4 border-bottom pb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <span class="text-muted d-block" style="font-size: 13px;">Remitente</span>
                            <strong class="fs-5 text-dark">{{ $mensaje->nombre }}</strong>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted d-block" style="font-size: 13px;">Correo Electrónico</span>
                            <a href="mailto:{{ $mensaje->email }}" class="fw-semibold text-primary fs-5 text-decoration-none">
                                <i class="bi bi-envelope"></i> {{ $mensaje->email }}
                            </a>
                        </div>
                    </div>

                    <div class="row mb-4 border-bottom pb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <span class="text-muted d-block" style="font-size: 13px;">Asunto</span>
                            <span class="fw-bold text-dark fs-5">{{ $mensaje->asunto }}</span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted d-block" style="font-size: 13px;">Fecha y Hora de Recepción</span>
                            <span class="fw-semibold text-dark fs-5">
                                <i class="bi bi-calendar-event"></i> {{ $mensaje->created_at->format('d/m/Y - h:i A') }}
                                <small class="text-muted">({{ $mensaje->created_at->diffForHumans() }})</small>
                            </span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <span class="text-muted d-block mb-2" style="font-size: 13px;">Contenido del Mensaje</span>
                        <div class="p-4 bg-light rounded-4 border border-light-subtle text-dark fs-6" style="white-space: pre-wrap; line-height: 1.6;">{{ $mensaje->mensaje }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-12">
            <!-- Action Card -->
            <div class="card shadow-sm rounded-4 border-0 mb-4">
                <div class="card-header bg-white border-0 py-3 fw-bold text-dark border-bottom">
                    Acciones de Gestión
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.mensajes.estado', $mensaje->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-4">
                            <label for="estado" class="form-label fw-bold">Actualizar Estado</label>
                            <select name="estado" id="estado" class="form-select" onchange="this.form.submit()">
                                <option value="Pendiente" {{ $mensaje->estado === 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
                                <option value="Leído" {{ $mensaje->estado === 'Leído' ? 'selected' : '' }}>Leído</option>
                                <option value="Respondido" {{ $mensaje->estado === 'Respondido' ? 'selected' : '' }}>Respondido</option>
                            </select>
                            <small class="text-muted mt-1 d-block">Cambiar a "Respondido" cuando se haya atendido al cliente.</small>
                        </div>
                    </form>

                    <div class="border-top pt-3 d-flex flex-column gap-2">
                        <a href="mailto:{{ $mensaje->email }}?subject=RE: {{ rawurlencode($mensaje->asunto) }}" class="btn btn-primary w-100 py-2 rounded-pill">
                            <i class="bi bi-reply"></i> Responder por Correo
                        </a>

                        @can('Eliminar Mensaje')
                            <form action="{{ route('admin.mensajes.destroy', $mensaje->id) }}" method="POST" id="deleteForm{{ $mensaje->id }}">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-outline-danger w-100 py-2 rounded-pill" onclick="confirmDelete()">
                                    <i class="bi bi-trash"></i> Eliminar Mensaje
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete() {
            Swal.fire({
                title: '¿Desea eliminar este mensaje?',
                text: "Esta acción es permanente y no se podrá revertir.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('deleteForm{{ $mensaje->id }}').submit();
                }
            });
        }
    </script>
@endsection
