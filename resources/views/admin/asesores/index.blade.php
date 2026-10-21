@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Listado de Asesores Comerciales</h1>
        @can('Crear Asesor')
            <a href="{{ route('admin.asesores.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Crear Nuevo Asesor
            </a>
        @endcan
    </div>
    <hr>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Asesores registrados</h4>
                </div>

                <div class="card-body">
                    <div class="alert alert-info">
                        Estos asesores aparecen en la página pública
                        <a href="{{ route('web.asesores') }}" target="_blank">/asesores</a>.
                        Cada tarjeta muestra su propio botón de WhatsApp y de correo.
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-striped align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 80px; text-align: center;">Nro</th>
                                    <th style="width: 90px; text-align: center;">Foto</th>
                                    <th>Nombre</th>
                                    <th>Cargo</th>
                                    <th style="width: 150px; text-align: center;">WhatsApp</th>
                                    <th>Correo</th>
                                    <th style="width: 80px; text-align: center;">Orden</th>
                                    <th style="width: 120px; text-align: center;">Estado</th>
                                    <th style="width: 150px; text-align: center;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $nro = ($asesores->currentPage() - 1) * $asesores->perPage() + 1;
                                @endphp
                                @forelse ($asesores as $asesor)
                                    <tr>
                                        <td style="text-align: center;">{{ $nro++ }}</td>
                                        <td style="text-align: center;">
                                            @if ($asesor->ruta_foto)
                                                <img src="{{ $asesor->ruta_foto }}" alt="{{ $asesor->nombre }}"
                                                    class="rounded-circle" style="width: 56px; height: 56px; object-fit: cover;">
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary rounded-circle"
                                                    style="width: 56px; height: 56px; line-height: 56px;">{{ $asesor->iniciales }}</span>
                                            @endif
                                        </td>
                                        <td class="fw-bold">{{ $asesor->nombre }}</td>
                                        <td>{{ $asesor->cargo ?: '—' }}</td>
                                        <td style="text-align: center;">
                                            @if ($asesor->enlace_whatsapp)
                                                <a href="{{ $asesor->enlace_whatsapp }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success">
                                                    <i class="bi bi-whatsapp"></i> {{ $asesor->whatsapp }}
                                                </a>
                                                @if (strlen(preg_replace('/\D+/', '', (string) $asesor->whatsapp)) === 9)
                                                    <small class="text-warning d-block mt-1">
                                                        Le falta el código de país. Escribe 51 delante (ej. 51{{ preg_replace('/\D+/', '', (string) $asesor->whatsapp) }}) o el botón no abre el chat.
                                                    </small>
                                                @endif
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $asesor->email ?: '—' }}</td>
                                        <td style="text-align: center;">{{ $asesor->orden }}</td>
                                        <td style="text-align: center;">
                                            @can('Editar Asesor')
                                                <form action="{{ route('admin.asesores.toggle', $asesor->id) }}" method="POST">
                                                    @csrf
                                                    @if ($asesor->activo)
                                                        <button type="submit" class="btn btn-success btn-sm px-3 rounded-pill">Visible</button>
                                                    @else
                                                        <button type="submit" class="btn btn-secondary btn-sm px-3 rounded-pill">Oculto</button>
                                                    @endif
                                                </form>
                                            @else
                                                <span class="badge bg-secondary">{{ $asesor->activo ? 'Visible' : 'Oculto' }}</span>
                                            @endcan
                                        </td>
                                        <td style="text-align: center;">
                                            <div class="btn-group btn-group-sm" role="group">
                                                @can('Editar Asesor')
                                                    <a href="{{ route('admin.asesores.edit', $asesor->id) }}" class="btn btn-success">
                                                        <i class="bi bi-pencil"></i> Editar
                                                    </a>
                                                @endcan
                                                @can('Eliminar Asesor')
                                                    <button type="button" class="btn btn-danger" onclick="confirmarEliminar{{ $asesor->id }}(event)">
                                                        <i class="bi bi-person-dash"></i> Quitar
                                                    </button>

                                                    <form action="{{ route('admin.asesores.destroy', $asesor->id) }}" method="POST" id="form-eliminar-{{ $asesor->id }}" style="display: none;">
                                                        @csrf
                                                        @method('DELETE')
                                                    </form>

                                                    <script>
                                                        function confirmarEliminar{{ $asesor->id }}(event) {
                                                            event.preventDefault();
                                                            Swal.fire({
                                                                title: '¿Quitar a {{ $asesor->nombre }} de la tienda?',
                                                                text: "Deja de aparecer en /asesores de inmediato. El registro se conserva y puedes devolverlo a mostrar.",
                                                                icon: 'warning',
                                                                showCancelButton: true,
                                                                confirmButtonColor: '#d33',
                                                                cancelButtonColor: '#3085d6',
                                                                confirmButtonText: 'Sí, quitar',
                                                                cancelButtonText: 'Cancelar'
                                                            }).then((result) => {
                                                                if (result.isConfirmed) {
                                                                    document.getElementById('form-eliminar-{{ $asesor->id }}').submit();
                                                                }
                                                            });
                                                        }
                                                    </script>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4 text-muted">
                                            Todavía no hay asesores. Crea el primero para que aparezca en la tienda.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($asesores->hasPages())
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted">
                                Mostrando {{ $asesores->firstItem() }} a {{ $asesores->lastItem() }} de {{ $asesores->total() }} asesores
                            </div>
                            <div>
                                {{ $asesores->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
